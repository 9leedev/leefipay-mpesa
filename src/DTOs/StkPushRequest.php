<?php

declare(strict_types=1);

namespace LeefiPay\Mpesa\DTOs;

final class StkPushRequest
{
    public function __construct(
        public readonly int $amount,
        public readonly string $phone,
        public readonly int $paymentChannelId,
        public readonly ?string $customerName = null,
        public readonly ?string $notes = null,
        public readonly ?string $idempotencyKey = null,
        public readonly ?string $accountReference = null,
        public readonly ?string $mode = null,
        public readonly ?string $payableType = null,
        public readonly ?int $payableId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            amount: (int) ($data['amount'] ?? 0),
            phone: (string) ($data['phone'] ?? ''),
            paymentChannelId: (int) ($data['payment_channel_id'] ?? $data['paymentChannelId'] ?? 0),
            customerName: isset($data['customer_name']) ? (string) $data['customer_name'] : (isset($data['customerName']) ? (string) $data['customerName'] : null),
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            idempotencyKey: isset($data['idempotency_key']) ? (string) $data['idempotency_key'] : (isset($data['idempotencyKey']) ? (string) $data['idempotencyKey'] : null),
            accountReference: isset($data['account_reference']) ? (string) $data['account_reference'] : (isset($data['accountReference']) ? (string) $data['accountReference'] : (isset($data['reference']) ? (string) $data['reference'] : null)),
            mode: isset($data['mode']) ? (string) $data['mode'] : null,
            payableType: isset($data['payable_type']) ? (string) $data['payable_type'] : null,
            payableId: isset($data['payable_id']) ? (int) $data['payable_id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'amount' => $this->amount,
            'phone' => $this->phone,
            'payment_channel_id' => $this->paymentChannelId,
            'customer_name' => $this->customerName,
            'notes' => $this->notes,
            'idempotency_key' => $this->idempotencyKey,
            'account_reference' => $this->accountReference,
            'mode' => $this->mode,
            'payable_type' => $this->payableType,
            'payable_id' => $this->payableId,
        ], static fn ($value) => $value !== null && $value !== '');
    }
}
