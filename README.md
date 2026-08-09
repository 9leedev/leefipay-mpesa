# LeefiPay M-Pesa for Laravel

[![tests](https://github.com/9leedev/leefipay-mpesa/actions/workflows/tests.yml/badge.svg)](https://github.com/9leedev/leefipay-mpesa/actions/workflows/tests.yml)

Official Laravel package for integrating applications with the **LeefiPay M-Pesa Open API**.

API base: [https://leefipay.com/api/v1/](https://leefipay.com/api/v1/) · Docs: [https://leefipay.com/developers](https://leefipay.com/developers)

This package talks to LeefiPay’s public REST API (`/api/v1`) using Sanctum Bearer tokens. It does **not** call Safaricom Daraja directly — LeefiPay handles Daraja on your behalf.

## Requirements

| Laravel | PHP | Status |
|---------|-----|--------|
| 9.x | 8.1+ | Supported |
| 10.x | 8.1+ | Supported |
| 11.x | 8.2+ | Supported |
| 12.x | 8.2+ | Supported |
| 13.x | 8.3+ | Supported |

Compatibility is enforced by GitHub Actions: package PHPUnit tests (HTTP-faked) plus install into a fresh Laravel application for each matrix entry. Mark rows as **Tested** only after the Actions workflow is green on `main`.

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
composer validate --strict
composer test
```

CI also installs the package into a temporary Laravel app for each supported major version (package discovery, config publish, container + facade resolution). No real LeefiPay / M-Pesa API calls are made.

## Versioning

Semantic Versioning. Current development line: **0.1.x**. Do not treat this as v1.0.0 until the full Laravel matrix is green in CI.

## License

MIT — see [LICENSE](LICENSE).
