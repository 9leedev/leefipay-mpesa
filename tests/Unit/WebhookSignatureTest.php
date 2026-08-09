<?php

declare(strict_types=1);

namespace LeefiPay\Mpesa\Tests\Unit;

use LeefiPay\Mpesa\DTOs\StkPushRequest;
use LeefiPay\Mpesa\Support\WebhookSignature;
use LeefiPay\Mpesa\Tests\TestCase;

class WebhookSignatureTest extends TestCase
{
    public function test_valid_signature_passes(): void
    {
        $payload = '{"event":"payment.successful","payment_reference":"ABC"}';
        $timestamp = (string) time();
        $secret = 'whsec_test_secret';
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        $this->assertTrue(WebhookSignature::verify($payload, $signature, $timestamp, $secret));
    }

    public function test_invalid_signature_fails(): void
    {
        $payload = '{"event":"payment.successful"}';
        $timestamp = (string) time();

        $this->assertFalse(WebhookSignature::verify($payload, 'bad', $timestamp, 'whsec_test_secret'));
    }

    public function test_expired_timestamp_fails(): void
    {
        $payload = '{"event":"payment.successful"}';
        $timestamp = (string) (time() - 10_000);
        $secret = 'whsec_test_secret';
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        $this->assertFalse(WebhookSignature::verify($payload, $signature, $timestamp, $secret, 300));
    }

    public function test_stk_push_request_maps_reference_alias(): void
    {
        $request = StkPushRequest::fromArray([
            'amount' => 100,
            'phone' => '254700000000',
            'payment_channel_id' => 3,
            'reference' => 'ORDER-1001',
            'description' => 'ignored by API — use notes',
            'notes' => 'Payment for Order 1001',
        ]);

        $this->assertSame('ORDER-1001', $request->accountReference);
        $this->assertSame(100, $request->toArray()['amount']);
        $this->assertSame('Payment for Order 1001', $request->toArray()['notes']);
    }
}
