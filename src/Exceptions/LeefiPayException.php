<?php

declare(strict_types=1);

namespace LeefiPay\Mpesa\Exceptions;

use Exception;
use Throwable;

class LeefiPayException extends Exception
{
    /**
     * @param  array<string, mixed>|null  $response
     */
    public function __construct(
        string $message = '',
        protected ?int $statusCode = null,
        protected ?array $response = null,
        protected ?string $errorCode = null,
        protected ?string $requestId = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
    }

    public function statusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function response(): ?array
    {
        return $this->response;
    }

    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    public function requestId(): ?string
    {
        return $this->requestId;
    }
}
