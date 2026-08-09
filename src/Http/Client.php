<?php

declare(strict_types=1);

namespace LeefiPay\Mpesa\Http;

use Illuminate\Http\Client\ConnectionException as IlluminateConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use LeefiPay\Mpesa\DTOs\ApiResponse;
use LeefiPay\Mpesa\Exceptions\ApiException;
use LeefiPay\Mpesa\Exceptions\AuthenticationException;
use LeefiPay\Mpesa\Exceptions\ConnectionException;
use LeefiPay\Mpesa\Exceptions\LeefiPayException;
use LeefiPay\Mpesa\Exceptions\RateLimitException;
use LeefiPay\Mpesa\Exceptions\TimeoutException;
use LeefiPay\Mpesa\Exceptions\ValidationException;
use Throwable;

/**
 * Low-level HTTP client for the LeefiPay Open API (/api/v1).
 */
class Client
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected array $config,
    ) {}

    /**
     * @param  array<string, mixed>|null  $query
     */
    public function get(string $path, ?array $query = null, bool $auth = true): ApiResponse
    {
        return $this->send('get', $path, query: $query, auth: $auth, retryable: true);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @param  array<string, string>  $headers
     */
    public function post(string $path, ?array $payload = null, bool $auth = true, bool $retryable = false, array $headers = []): ApiResponse
    {
        return $this->send('post', $path, payload: $payload, auth: $auth, retryable: $retryable, headers: $headers);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function put(string $path, ?array $payload = null, bool $auth = true): ApiResponse
    {
        return $this->send('put', $path, payload: $payload, auth: $auth, retryable: false);
    }

    /**
     * @param  array<string, mixed>|null  $query
     */
    public function delete(string $path, ?array $query = null, bool $auth = true): ApiResponse
    {
        return $this->send('delete', $path, query: $query, auth: $auth, retryable: false);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @param  array<string, mixed>|null  $query
     * @param  array<string, string>  $headers
     */
    protected function send(
        string $method,
        string $path,
        ?array $payload = null,
        ?array $query = null,
        bool $auth = true,
        bool $retryable = false,
        array $headers = [],
    ): ApiResponse {
        $url = $this->url($path);
        $request = $this->pending($auth, $retryable, $headers);

        try {
            /** @var Response $response */
            $response = match (strtolower($method)) {
                'get' => $request->get($url, $query ?? []),
                'post' => $request->post($url, $payload ?? []),
                'put' => $request->put($url, $payload ?? []),
                'delete' => $request->delete($url, $query ?? []),
                default => throw new LeefiPayException('Unsupported HTTP method: '.$method),
            };
        } catch (IlluminateConnectionException $e) {
            $this->log('connection_error', $method, $path, null, $e->getMessage());

            if (str_contains(strtolower($e->getMessage()), 'timed out')
                || str_contains(strtolower($e->getMessage()), 'timeout')) {
                throw new TimeoutException('LeefiPay API request timed out.', previous: $e);
            }

            throw new ConnectionException('Unable to connect to LeefiPay API.', previous: $e);
        } catch (RequestException $e) {
            $response = $e->response;
            if ($response === null) {
                throw new ApiException('LeefiPay API request failed.', previous: $e);
            }

            return $this->handleResponse($response, $method, $path);
        } catch (LeefiPayException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->log('unexpected_error', $method, $path, null, $e->getMessage());
            throw new ApiException('Unexpected LeefiPay client error.', previous: $e);
        }

        return $this->handleResponse($response, $method, $path);
    }

    protected function handleResponse(Response $response, string $method, string $path): ApiResponse
    {
        $status = $response->status();
        $json = $response->json();
        $payload = is_array($json) ? $json : ['success' => false, 'message' => $response->body(), 'data' => null];

        $this->log('response', $method, $path, $status);

        if ($status === 401) {
            throw new AuthenticationException(
                (string) ($payload['message'] ?? 'Unauthenticated.'),
                $status,
                $payload,
                isset($payload['error']) ? (string) $payload['error'] : 'unauthenticated',
            );
        }

        if ($status === 422) {
            throw new ValidationException(
                (string) ($payload['message'] ?? 'Validation failed.'),
                $status,
                $payload,
                is_array($payload['errors'] ?? null) ? $payload['errors'] : [],
                isset($payload['error']) ? (string) $payload['error'] : 'validation_error',
            );
        }

        if ($status === 429) {
            throw new RateLimitException(
                (string) ($payload['message'] ?? 'Too many requests.'),
                $status,
                $payload,
                isset($payload['error']) ? (string) $payload['error'] : 'rate_limited',
            );
        }

        if ($status >= 400) {
            throw new ApiException(
                (string) ($payload['message'] ?? 'LeefiPay API error.'),
                $status,
                $payload,
                isset($payload['error']) ? (string) $payload['error'] : null,
            );
        }

        return ApiResponse::fromHttp($payload, $status);
    }

    /**
     * @param  array<string, string>  $headers
     */
    protected function pending(bool $auth, bool $retryable, array $headers = []): PendingRequest
    {
        $request = Http::acceptJson()
            ->asJson()
            ->timeout((int) ($this->config['timeout'] ?? 30))
            ->connectTimeout((int) ($this->config['connect_timeout'] ?? 10))
            ->withHeaders(array_merge([
                'User-Agent' => 'leefipay-mpesa-php/0.1.0',
            ], $headers));

        if ($auth) {
            $token = (string) ($this->config['api_key'] ?? '');
            if ($token === '') {
                throw new AuthenticationException('LEEFIPAY_API_KEY (Bearer token) is not configured.');
            }
            $request = $request->withToken($token);
        }

        $retry = $this->config['retry'] ?? [];
        if ($retryable && ($retry['enabled'] ?? true)) {
            $times = max(0, (int) ($retry['times'] ?? 3));
            $sleep = max(0, (int) ($retry['sleep'] ?? 500));
            if ($times > 0) {
                $request = $request->retry($times, $sleep, function ($exception) {
                    // Only retry transport failures — never assume financial POST safety here.
                    return $exception instanceof IlluminateConnectionException;
                }, throw: false);
            }
        }

        return $request;
    }

    protected function url(string $path): string
    {
        $base = rtrim((string) ($this->config['base_url'] ?? ''), '/');
        $path = '/'.ltrim($path, '/');

        if (! str_starts_with($path, '/api/')) {
            $path = '/api/v1'.$path;
        }

        return $base.$path;
    }

    protected function log(string $event, string $method, string $path, ?int $status, ?string $detail = null): void
    {
        if (! ($this->config['logging']['enabled'] ?? false)) {
            return;
        }

        $channel = $this->config['logging']['channel'] ?? null;
        $logger = $channel ? Log::channel($channel) : Log::getFacadeRoot();

        $logger->info('leefipay.mpesa.'.$event, array_filter([
            'method' => strtoupper($method),
            'path' => $path,
            'status' => $status,
            'detail' => $detail,
            // Never log tokens, secrets, or full payloads.
        ], static fn ($v) => $v !== null));
    }
}
