# LeefiPay M-Pesa for Laravel

Official Laravel package for integrating applications with the **LeefiPay M-Pesa Open API**.

API base: [https://leefipay.com/api/v1/](https://leefipay.com/api/v1/) · Docs: [https://leefipay.com/developers](https://leefipay.com/developers)

This package talks to LeefiPay’s public REST API (`/api/v1`) using Sanctum Bearer tokens. It does **not** call Safaricom Daraja directly — LeefiPay handles Daraja on your behalf.

## Requirements

| Requirement | Supported |
|-------------|-----------|
| PHP | **8.1+** |
| Laravel | **9 · 10 · 11 · 12 · 13** |

## Installation

```bash
composer require leefipay/mpesa
```

Publish config (optional):

```bash
php artisan vendor:publish --tag=leefipay-config
```

## Configuration

```env
LEEFIPAY_BASE_URL=https://leefipay.com
LEEFIPAY_API_KEY=your-sanctum-secret-bearer-token
LEEFIPAY_ENVIRONMENT=sandbox
LEEFIPAY_TIMEOUT=30
LEEFIPAY_CONNECT_TIMEOUT=10

# Outbound webhooks (LeefiPay → your app)
LEEFIPAY_WEBHOOKS_ENABLED=true
LEEFIPAY_WEBHOOK_PATH=leefipay/webhooks/mpesa
LEEFIPAY_WEBHOOK_SECRET=whsec_...
```

Create an API credential in the LeefiPay dashboard (**API & Integrations** or Developer console). Use the **secret** token as `LEEFIPAY_API_KEY`. The public key (`lp_pk_…`) is **not** used for request authentication.

`LEEFIPAY_ENVIRONMENT` is informational (sandbox vs production host is controlled by `LEEFIPAY_BASE_URL`).

## Basic usage

```php
use LeefiPay\Mpesa\Facades\LeefiPay;

$response = LeefiPay::stkPush([
    'phone' => '254700000000',
    'amount' => 100,
    'payment_channel_id' => 1,
    'reference' => 'ORDER-1001', // sent as account_reference
    'notes' => 'Payment for Order 1001',
    'idempotency_key' => 'order-1001',
]);

if ($response->success()) {
    $response->reference();
    $response->waitingUrl();
    $response->checkoutRequestId();
}
```

Dependency injection:

```php
use LeefiPay\Mpesa\Contracts\LeefiPayClientInterface;

public function __construct(private LeefiPayClientInterface $leefiPay) {}
```

## STK Push

Required fields (Open API):

| Field | Notes |
|-------|--------|
| `amount` | Integer KES ≥ 1 |
| `phone` | Customer MSISDN |
| `payment_channel_id` | Till / Paybill channel from LeefiPay |

Optional: `customer_name`, `notes`, `idempotency_key` / `Idempotency-Key`, `account_reference` (alias `reference`), `mode` (`stk`\|`manual`\|`auto`).

## Transaction status & verify

```php
$status = LeefiPay::paymentStatus('PAY-12');

$verified = LeefiPay::verifyTransaction([
    'reference' => 'PAY-12',
    // or 'uuid' / 'checkout_request_id'
]);
```

## Payment channels

```php
$channels = LeefiPay::paymentChannels();
```

## Connection test

```php
LeefiPay::testConnection();
LeefiPay::me();
LeefiPay::health(); // unauthenticated
```

## Webhooks (LeefiPay → your app)

Register a webhook URL in the LeefiPay dashboard pointing to:

```text
https://your-app.test/leefipay/webhooks/mpesa
```

Set `LEEFIPAY_WEBHOOK_SECRET` to the `whsec_…` secret.

The package verifies:

```text
X-LeefiPay-Signature = HMAC-SHA256(timestamp + "." + rawBody, secret)
X-LeefiPay-Timestamp
X-LeefiPay-Event
```

Dispatched events:

- `LeefiPay\Mpesa\Events\WebhookReceived`
- `LeefiPay\Mpesa\Events\PaymentSuccessful` (`payment.successful`)
- `LeefiPay\Mpesa\Events\PaymentFailed` (`payment.failed`)
- `LeefiPay\Mpesa\Events\InvoicePaid` (`invoice.paid`)

```php
use LeefiPay\Mpesa\Events\PaymentSuccessful;

Event::listen(PaymentSuccessful::class, function ($event) {
    $event->webhookEvent->paymentReference();
});
```

## Error handling

```php
use LeefiPay\Mpesa\Exceptions\ApiException;
use LeefiPay\Mpesa\Exceptions\AuthenticationException;
use LeefiPay\Mpesa\Exceptions\ValidationException;
use LeefiPay\Mpesa\Exceptions\RateLimitException;

try {
    LeefiPay::stkPush([...]);
} catch (ValidationException $e) {
    $e->errors();
} catch (AuthenticationException $e) {
    // Invalid / missing Bearer token
} catch (RateLimitException $e) {
    // HTTP 429
} catch (ApiException $e) {
    $e->statusCode();
    $e->errorCode(); // e.g. subscription_expired
    $e->response();
}
```

Credentials are never included in exception messages or package logs.

## Retries

- **GET** requests may retry on **connection failures** when `LEEFIPAY_RETRY_ENABLED=true`.
- **Payment POSTs** (STK, cash, manual, retry) are **never** auto-retried. Use `idempotency_key` and retry yourself if needed.

## Not currently supported

These are **not** exposed on the LeefiPay public Open API and are therefore **not** implemented:

- B2C
- C2B URL registration
- Account balance
- Transaction reversal
- Direct Safaricom Daraja OAuth / passkey usage

## Security

- Store tokens and webhook secrets in environment variables only.
- Prefer HTTPS for `LEEFIPAY_BASE_URL` and webhook URLs.
- Rotate compromised credentials in the LeefiPay dashboard.

See [SECURITY.md](SECURITY.md).

## Testing

```bash
composer install
composer test
```

## Versioning

Semantic Versioning. Current development line: **0.1.x**.

## License

MIT — see [LICENSE](LICENSE).
