<?php

declare(strict_types=1);

namespace LeefiPay\Mpesa\Events;

use LeefiPay\Mpesa\DTOs\WebhookEvent;

class PaymentSuccessful
{
    public function __construct(
        public WebhookEvent $webhookEvent,
    ) {}
}
