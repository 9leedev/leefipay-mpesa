<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | LeefiPay Open API base URL
    |--------------------------------------------------------------------------
    |
    | Sandbox and production use the same path prefix (/api/v1). Point this at
    | your LeefiPay host (e.g. https://leefipay.com). Do not append /api/v1.
    |
    */
    'base_url' => rtrim((string) env('LEEFIPAY_BASE_URL', 'https://leefipay.com'), '/'),

    /*
    |--------------------------------------------------------------------------
    | API credentials
    |--------------------------------------------------------------------------
    |
    | Use the **secret** Sanctum Bearer token from the LeefiPay dashboard
    | (API & Integrations / Developer console). The public key (lp_pk_…) is
    | not used for request authentication.
    |
    | LEEFIPAY_API_KEY and LEEFIPAY_API_TOKEN are aliases for the same Bearer token.
    |
    */
    'api_key' => env('LEEFIPAY_API_KEY', env('LEEFIPAY_API_TOKEN')),

    /*
    |--------------------------------------------------------------------------
    | Optional password-token helpers
    |--------------------------------------------------------------------------
    |
    | Only needed if you issue tokens via POST /api/v1/auth/token instead of
    | using a dashboard-generated secret key.
    |
    */
    'api_secret' => env('LEEFIPAY_API_SECRET'),

    'environment' => env('LEEFIPAY_ENVIRONMENT', 'sandbox'),

    'timeout' => (int) env('LEEFIPAY_TIMEOUT', 30),

    'connect_timeout' => (int) env('LEEFIPAY_CONNECT_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Retries
    |--------------------------------------------------------------------------
    |
    | Retries apply only to safe/idempotent reads (GET) and connection failures
    | before the server accepts the request. Payment-mutating POSTs (STK, cash,
    | manual, retry) are NEVER auto-retried — use Idempotency-Key and call again
    | yourself if you need safe retries.
    |
    */
    'retry' => [
        'enabled' => (bool) env('LEEFIPAY_RETRY_ENABLED', true),
        'times' => (int) env('LEEFIPAY_RETRY_TIMES', 3),
        'sleep' => (int) env('LEEFIPAY_RETRY_SLEEP', 500),
    ],

    'logging' => [
        'enabled' => (bool) env('LEEFIPAY_LOGGING_ENABLED', false),
        'channel' => env('LEEFIPAY_LOG_CHANNEL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Outbound webhook verification (LeefiPay → your app)
    |--------------------------------------------------------------------------
    |
    | Secret from the LeefiPay dashboard webhook endpoint (whsec_…).
    |
    */
    'webhook' => [
        'enabled' => (bool) env('LEEFIPAY_WEBHOOKS_ENABLED', true),
        'path' => env('LEEFIPAY_WEBHOOK_PATH', 'leefipay/webhooks/mpesa'),
        'secret' => env('LEEFIPAY_WEBHOOK_SECRET'),
        'tolerance_seconds' => (int) env('LEEFIPAY_WEBHOOK_TOLERANCE', 300),
    ],
];
