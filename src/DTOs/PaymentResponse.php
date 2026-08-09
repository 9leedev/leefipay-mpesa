<?php

declare(strict_types=1);

namespace LeefiPay\Mpesa\DTOs;

final class PaymentResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly ApiResponse $response,
        public readonly array $raw,
    ) {}

    public static function fromApiResponse(ApiResponse $response): self
    {
        $data = $response->dataArray() ?? [];

        return new self($response, $data);
    }

    public function success(): bool
    {
        return $this->response->success();
    }

    public function message(): string
    {
        return $this->response->message();
    }

    public function id(): ?int
    {
        return isset($this->raw['id']) ? (int) $this->raw['id'] : null;
    }

    public function uuid(): ?string
    {
        return isset($this->raw['uuid']) ? (string) $this->raw['uuid'] : null;
    }

    public function reference(): ?string
    {
        return isset($this->raw['reference']) ? (string) $this->raw['reference'] : null;
    }

    public function status(): ?string
    {
        return isset($this->raw['status']) ? (string) $this->raw['status'] : null;
    }

    public function amount(): ?int
    {
        return isset($this->raw['amount']) ? (int) $this->raw['amount'] : null;
    }

    public function phone(): ?string
    {
        return isset($this->raw['phone']) ? (string) $this->raw['phone'] : null;
    }

    public function mpesaReceipt(): ?string
    {
        return isset($this->raw['mpesa_receipt']) ? (string) $this->raw['mpesa_receipt'] : (
            isset($this->raw['receipt_number']) ? (string) $this->raw['receipt_number'] : null
        );
    }

    public function checkoutRequestId(): ?string
    {
        return isset($this->raw['checkout_request_id']) ? (string) $this->raw['checkout_request_id'] : null;
    }

    public function waitingUrl(): ?string
    {
        return isset($this->raw['waiting_url']) ? (string) $this->raw['waiting_url'] : null;
    }

    public function transactionId(): ?string
    {
        return $this->mpesaReceipt() ?? $this->reference();
    }

    public function isCompleted(): bool
    {
        $status = strtolower((string) $this->status());

        return in_array($status, ['completed', 'successful', 'success', 'paid'], true);
    }

    public function isFailed(): bool
    {
        $status = strtolower((string) $this->status());

        return in_array($status, ['failed', 'cancelled', 'expired'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function raw(): array
    {
        return $this->raw;
    }

    public function api(): ApiResponse
    {
        return $this->response;
    }
}
