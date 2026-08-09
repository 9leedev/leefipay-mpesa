<?php

declare(strict_types=1);

namespace LeefiPay\Mpesa\Exceptions;

class ValidationException extends LeefiPayException
{
    /**
     * @param  array<string, mixed>  $errors
     */
    public function __construct(
        string $message = 'Validation failed',
        ?int $statusCode = 422,
        ?array $response = null,
        protected array $errors = [],
        ?string $errorCode = 'validation_error',
    ) {
        parent::__construct($message, $statusCode, $response, $errorCode);
    }

    /**
     * @return array<string, mixed>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
