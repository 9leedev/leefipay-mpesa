<?php

declare(strict_types=1);

namespace LeefiPay\Mpesa\Services;

use LeefiPay\Mpesa\Contracts\LeefiPayClientInterface;
use LeefiPay\Mpesa\DTOs\ApiResponse;
use LeefiPay\Mpesa\DTOs\PaymentResponse;
use LeefiPay\Mpesa\DTOs\StkPushRequest;
use LeefiPay\Mpesa\Exceptions\ValidationException;
use LeefiPay\Mpesa\Http\Client;

/**
 * High-level LeefiPay Open API client (Sanctum Bearer /api/v1).
 *
 * Not currently supported by the public Open API: B2C, C2B register,
 * account balance, or transaction reversal.
 */
class LeefiPayClient implements LeefiPayClientInterface
{
    public function __construct(
        protected Client $http,
    ) {}

    public function stkPush(array|StkPushRequest $payload): PaymentResponse
    {
        $request = $payload instanceof StkPushRequest ? $payload : StkPushRequest::fromArray($payload);

        if ($request->amount < 1 || $request->phone === '' || $request->paymentChannelId < 1) {
            throw new ValidationException(
                'amount, phone, and payment_channel_id are required for STK Push.',
                errors: [
                    'amount' => $request->amount < 1 ? ['Required integer >= 1'] : [],
                    'phone' => $request->phone === '' ? ['Required'] : [],
                    'payment_channel_id' => $request->paymentChannelId < 1 ? ['Required'] : [],
                ],
            );
        }

        $headers = [];
        if ($request->idempotencyKey) {
            $headers['Idempotency-Key'] = $request->idempotencyKey;
        }

        $response = $this->http->post('/payments/stk', $request->toArray(), headers: $headers);

        return PaymentResponse::fromApiResponse($response);
    }

    public function paymentStatus(string $reference): PaymentResponse
    {
        $response = $this->http->get('/payments/status/'.rawurlencode($reference));

        return PaymentResponse::fromApiResponse($response);
    }

    public function verifyTransaction(array $query): PaymentResponse
    {
        $response = $this->http->get('/transaction/verify', $query);

        return PaymentResponse::fromApiResponse($response);
    }

    public function retryPayment(int $paymentId, ?string $phone = null): PaymentResponse
    {
        $payload = array_filter(['phone' => $phone], static fn ($v) => $v !== null && $v !== '');
        $response = $this->http->post('/payments/'.$paymentId.'/retry', $payload);

        return PaymentResponse::fromApiResponse($response);
    }

    public function cashPayment(array $payload): PaymentResponse
    {
        $headers = [];
        if (! empty($payload['idempotency_key'])) {
            $headers['Idempotency-Key'] = (string) $payload['idempotency_key'];
        }

        $response = $this->http->post('/payments/cash', $payload, headers: $headers);

        return PaymentResponse::fromApiResponse($response);
    }

    public function manualPayment(array $payload): PaymentResponse
    {
        $headers = [];
        if (! empty($payload['idempotency_key'])) {
            $headers['Idempotency-Key'] = (string) $payload['idempotency_key'];
        }

        $response = $this->http->post('/payments/manual', $payload, headers: $headers);

        return PaymentResponse::fromApiResponse($response);
    }

    public function payments(array $query = []): ApiResponse
    {
        return $this->http->get('/payments', $query);
    }

    public function payment(int $id): PaymentResponse
    {
        return PaymentResponse::fromApiResponse($this->http->get('/payments/'.$id));
    }

    public function paymentChannels(array $query = []): ApiResponse
    {
        return $this->http->get('/payment-channels', $query);
    }

    public function testConnection(): ApiResponse
    {
        return $this->http->post('/integrations/test', []);
    }

    public function me(): ApiResponse
    {
        return $this->http->get('/auth/me');
    }

    public function health(): ApiResponse
    {
        return $this->http->get('/health', auth: false);
    }

    public function createToken(array $credentials): ApiResponse
    {
        return $this->http->post('/auth/token', $credentials, auth: false);
    }

    public function revokeToken(): ApiResponse
    {
        return $this->http->post('/auth/revoke', []);
    }

    /**
     * Escape hatch for advanced callers.
     */
    public function http(): Client
    {
        return $this->http;
    }
}
