<?php

declare(strict_types=1);

namespace LeefiPay\Mpesa\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use LeefiPay\Mpesa\Events\PaymentSuccessful;
use LeefiPay\Mpesa\Events\WebhookReceived;
use LeefiPay\Mpesa\Exceptions\ApiException;
use LeefiPay\Mpesa\Exceptions\AuthenticationException;
use LeefiPay\Mpesa\Exceptions\ValidationException;
use LeefiPay\Mpesa\Facades\LeefiPay;
use LeefiPay\Mpesa\Tests\TestCase;

class LeefiPayClientTest extends TestCase
{
    public function test_stk_push_success(): void
    {
        Http::fake([
            'api.example.test/api/v1/payments/stk' => Http::response([
                'success' => true,
                'message' => 'STK payment initiated',
                'data' => [
                    'id' => 12,
                    'reference' => 'PAY-12',
                    'status' => 'processing',
                    'amount' => 100,
                    'phone' => '254700000000',
                    'waiting_url' => 'https://api.example.test/pay/abc',
                    'checkout_request_id' => 'ws_CO_123',
                ],
            ], 201),
        ]);

        $response = LeefiPay::stkPush([
            'amount' => 100,
            'phone' => '254700000000',
            'payment_channel_id' => 1,
            'reference' => 'ORDER-1001',
            'idempotency_key' => 'ord-1001',
        ]);

        $this->assertTrue($response->success());
        $this->assertSame('PAY-12', $response->reference());
        $this->assertSame(100, $response->amount());
        $this->assertSame('ws_CO_123', $response->checkoutRequestId());

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.example.test/api/v1/payments/stk'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && $request->hasHeader('Idempotency-Key', 'ord-1001')
                && $request['amount'] === 100
                && $request['account_reference'] === 'ORDER-1001';
        });
    }

    public function test_stk_push_validation_error_from_api(): void
    {
        Http::fake([
            'api.example.test/api/v1/payments/stk' => Http::response([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => ['phone' => ['Invalid phone']],
            ], 422),
        ]);

        $this->expectException(ValidationException::class);

        LeefiPay::stkPush([
            'amount' => 100,
            'phone' => 'bad',
            'payment_channel_id' => 1,
        ]);
    }

    public function test_stk_push_local_validation(): void
    {
        $this->expectException(ValidationException::class);

        LeefiPay::stkPush([
            'amount' => 0,
            'phone' => '',
            'payment_channel_id' => 0,
        ]);
    }

    public function test_payment_status(): void
    {
        Http::fake([
            'api.example.test/api/v1/payments/status/PAY-12' => Http::response([
                'success' => true,
                'message' => 'OK',
                'data' => [
                    'reference' => 'PAY-12',
                    'status' => 'completed',
                    'mpesa_receipt' => 'ABC123',
                ],
            ], 200),
        ]);

        $response = LeefiPay::paymentStatus('PAY-12');

        $this->assertTrue($response->isCompleted());
        $this->assertSame('ABC123', $response->transactionId());
    }

    public function test_verify_transaction(): void
    {
        Http::fake([
            'api.example.test/api/v1/transaction/verify*' => Http::response([
                'success' => true,
                'message' => 'OK',
                'data' => ['reference' => 'PAY-12', 'status' => 'completed'],
            ], 200),
        ]);

        $response = LeefiPay::verifyTransaction(['reference' => 'PAY-12']);
        $this->assertSame('PAY-12', $response->reference());
    }

    public function test_authentication_failure(): void
    {
        Http::fake([
            'api.example.test/api/v1/auth/me' => Http::response([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401),
        ]);

        $this->expectException(AuthenticationException::class);
        LeefiPay::me();
    }

    public function test_api_error(): void
    {
        Http::fake([
            'api.example.test/api/v1/payment-channels' => Http::response([
                'success' => false,
                'message' => 'Subscription expired',
                'error' => 'subscription_expired',
            ], 402),
        ]);

        try {
            LeefiPay::paymentChannels();
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertSame(402, $e->statusCode());
            $this->assertSame('subscription_expired', $e->errorCode());
        }
    }

    public function test_health_does_not_require_auth_header(): void
    {
        Http::fake([
            'api.example.test/api/v1/health' => Http::response([
                'success' => true,
                'message' => 'OK',
                'data' => ['status' => 'ok'],
            ], 200),
        ]);

        $response = LeefiPay::health();
        $this->assertTrue($response->success());

        Http::assertSent(fn ($request) => ! $request->hasHeader('Authorization'));
    }

    public function test_webhook_accepts_valid_signature_and_dispatches_events(): void
    {
        Event::fake([WebhookReceived::class, PaymentSuccessful::class]);

        $payload = json_encode([
            'event' => 'payment.successful',
            'payment_reference' => 'PAY-99',
            'status' => 'paid',
        ], JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test_secret');

        $this->call(
            'POST',
            '/leefipay/webhooks/mpesa',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_LEEFIPAY_SIGNATURE' => $signature,
                'HTTP_X_LEEFIPAY_TIMESTAMP' => $timestamp,
                'HTTP_X_LEEFIPAY_EVENT' => 'payment.successful',
            ],
            $payload,
        )->assertOk()->assertJson(['success' => true]);

        Event::assertDispatched(WebhookReceived::class);
        Event::assertDispatched(PaymentSuccessful::class);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $this->call(
            'POST',
            '/leefipay/webhooks/mpesa',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_LEEFIPAY_SIGNATURE' => 'invalid',
                'HTTP_X_LEEFIPAY_TIMESTAMP' => (string) time(),
            ],
            '{"event":"payment.successful"}',
        )->assertStatus(401);
    }

    public function test_config_uses_custom_base_url(): void
    {
        config(['leefipay.base_url' => 'https://custom.leefipay.test']);

        // Re-bind client with new config
        $this->app->forgetInstance(\LeefiPay\Mpesa\Http\Client::class);
        $this->app->forgetInstance(\LeefiPay\Mpesa\Contracts\LeefiPayClientInterface::class);

        Http::fake([
            'custom.leefipay.test/api/v1/health' => Http::response([
                'success' => true,
                'message' => 'OK',
                'data' => [],
            ], 200),
        ]);

        LeefiPay::health();

        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://custom.leefipay.test/api/v1/health'));
    }
}
