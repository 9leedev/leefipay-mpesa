<?php

declare(strict_types=1);

namespace LeefiPay\Mpesa\Contracts;

use LeefiPay\Mpesa\DTOs\ApiResponse;
use LeefiPay\Mpesa\DTOs\PaymentResponse;
use LeefiPay\Mpesa\DTOs\StkPushRequest;

interface LeefiPayClientInterface
{
    /**
     * @param  array<string, mixed>|StkPushRequest  $payload
     */
    public function stkPush(array|StkPushRequest $payload): PaymentResponse;

    public function paymentStatus(string $reference): PaymentResponse;

    /**
     * @param  array{reference?: string, uuid?: string, checkout_request_id?: string}  $query
     */
    public function verifyTransaction(array $query): PaymentResponse;

    public function retryPayment(int $paymentId, ?string $phone = null): PaymentResponse;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function cashPayment(array $payload): PaymentResponse;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function manualPayment(array $payload): PaymentResponse;

    /**
     * @param  array<string, mixed>  $query
     */
    public function payments(array $query = []): ApiResponse;

    public function payment(int $id): PaymentResponse;

    /**
     * @param  array<string, mixed>  $query
     */
    public function paymentChannels(array $query = []): ApiResponse;

    public function testConnection(): ApiResponse;

    public function me(): ApiResponse;

    public function health(): ApiResponse;

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function createToken(array $credentials): ApiResponse;

    public function revokeToken(): ApiResponse;
}
