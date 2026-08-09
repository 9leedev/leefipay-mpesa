<?php

declare(strict_types=1);

namespace LeefiPay\Mpesa\Support;

use LeefiPay\Mpesa\Exceptions\WebhookSignatureException;

final class WebhookSignature
{
    /**
     * Verify LeefiPay outbound webhook HMAC.
     *
     * Signature = HMAC-SHA256(timestamp + "." + rawBody, secret)
     * Header: X-LeefiPay-Signature
     * Header: X-LeefiPay-Timestamp
     */
    public static function verify(
        string $payload,
        string $signature,
        string $timestamp,
        string $secret,
        int $toleranceSeconds = 300,
    ): bool {
        if ($secret === '' || $signature === '' || $timestamp === '') {
            return false;
        }

        if (! ctype_digit($timestamp)) {
            return false;
        }

        $age = abs(time() - (int) $timestamp);
        if ($toleranceSeconds > 0 && $age > $toleranceSeconds) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * @throws WebhookSignatureException
     */
    public static function assertValid(
        string $payload,
        string $signature,
        string $timestamp,
        string $secret,
        int $toleranceSeconds = 300,
    ): void {
        if (! self::verify($payload, $signature, $timestamp, $secret, $toleranceSeconds)) {
            throw new WebhookSignatureException('Invalid LeefiPay webhook signature.');
        }
    }
}
