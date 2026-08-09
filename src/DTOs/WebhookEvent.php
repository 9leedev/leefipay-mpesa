<?php

declare(strict_types=1);

namespace LeefiPay\Mpesa\DTOs;

final class WebhookEvent
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $event,
        public readonly array $payload,
        public readonly ?string $timestamp = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload, ?string $timestamp = null): self
    {
        return new self(
            event: (string) ($payload['event'] ?? 'unknown'),
            payload: $payload,
            timestamp: $timestamp,
        );
    }

    public function name(): string
    {
        return $this->event;
    }

    /**
     * @return array<string, mixed>
     */
    public function raw(): array
    {
        return $this->payload;
    }

    public function paymentReference(): ?string
    {
        return isset($this->payload['payment_reference']) ? (string) $this->payload['payment_reference'] : null;
    }

    public function invoiceId(): ?int
    {
        return isset($this->payload['invoice_id']) ? (int) $this->payload['invoice_id'] : null;
    }

    public function status(): ?string
    {
        return isset($this->payload['status']) ? (string) $this->payload['status'] : null;
    }
}
