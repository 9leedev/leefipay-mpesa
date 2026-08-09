<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use LeefiPay\Mpesa\Http\Controllers\WebhookController;

$path = trim((string) config('leefipay.webhook.path', 'leefipay/webhooks/mpesa'), '/');

Route::post($path, WebhookController::class)
    ->middleware('api')
    ->name('leefipay.webhooks.mpesa');
