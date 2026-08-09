<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use LeefiPay\Mpesa\Http\Controllers\WebhookController;

$path = trim((string) config('leefipay.webhook.path', 'leefipay/webhooks/mpesa'), '/');

// Intentionally no `api` / `web` middleware group: Laravel 11+ skeletons omit
// the `api` group by default, and `web` would enforce CSRF on inbound webhooks.
Route::post($path, WebhookController::class)
    ->name('leefipay.webhooks.mpesa');
