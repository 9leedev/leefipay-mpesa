<?php

declare(strict_types=1);

namespace LeefiPay\Mpesa\DTOs;

final class ApiResponse
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  array<string, mixed>|list<mixed>|null  $data
     * @param  array<string, mixed>|null  $meta
     * @param  array<string, mixed>|null  $errors
     */
    public function __construct(
        public readonly bool $ok,
        public readonly string $message,
        public readonly mixed $data,
        public readonly ?array $meta,
        public readonly ?array $errors,
        public readonly int $statusCode,
        public readonly array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromHttp(array $payload, int $statusCode): self
    {
        return new self(
            ok: (bool) ($payload['success'] ?? ($statusCode >= 200 && $statusCode < 300)),
            message: (string) ($payload['message'] ?? ''),
            data: $payload['data'] ?? null,
            meta: isset($payload['meta']) && is_array($payload['meta']) ? $payload['meta'] : null,
            errors: isset($payload['errors']) && is_array($payload['errors']) ? $payload['errors'] : null,
            statusCode: $statusCode,
            raw: $payload,
        );
    }

    public function success(): bool
    {
        return $this->ok;
    }

    public function message(): string
    {
        return $this->message;
    }

    /**
     * @return array<string, mixed>
     */
    public function raw(): array
    {
        return $this->raw;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function dataArray(): ?array
    {
        return is_array($this->data) ? $this->data : null;
    }
}
