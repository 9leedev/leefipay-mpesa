<?php

declare(strict_types=1);

namespace LeefiPay\Mpesa\Facades;

use Illuminate\Support\Facades\Facade;
use LeefiPay\Mpesa\Contracts\LeefiPayClientInterface;
use LeefiPay\Mpesa\Services\LeefiPayClient;

/**
 * @method static \LeefiPay\Mpesa\DTOs\PaymentResponse stkPush(array|\LeefiPay\Mpesa\DTOs\StkPushRequest $payload)
 * @method static \LeefiPay\Mpesa\DTOs\PaymentResponse paymentStatus(string $reference)
 * @method static \LeefiPay\Mpesa\DTOs\PaymentResponse verifyTransaction(array $query)
 * @method static \LeefiPay\Mpesa\DTOs\PaymentResponse retryPayment(int $paymentId, ?string $phone = null)
 * @method static \LeefiPay\Mpesa\DTOs\PaymentResponse cashPayment(array $payload)
 * @method static \LeefiPay\Mpesa\DTOs\PaymentResponse manualPayment(array $payload)
 * @method static \LeefiPay\Mpesa\DTOs\ApiResponse payments(array $query = [])
 * @method static \LeefiPay\Mpesa\DTOs\PaymentResponse payment(int $id)
 * @method static \LeefiPay\Mpesa\DTOs\ApiResponse paymentChannels(array $query = [])
 * @method static \LeefiPay\Mpesa\DTOs\ApiResponse testConnection()
 * @method static \LeefiPay\Mpesa\DTOs\ApiResponse me()
 * @method static \LeefiPay\Mpesa\DTOs\ApiResponse health()
 * @method static \LeefiPay\Mpesa\DTOs\ApiResponse createToken(array $credentials)
 * @method static \LeefiPay\Mpesa\DTOs\ApiResponse revokeToken()
 * @method static \LeefiPay\Mpesa\Http\Client http()
 *
 * @see LeefiPayClient
 */
class LeefiPay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LeefiPayClientInterface::class;
    }
}
