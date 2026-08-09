<?php

declare(strict_types=1);

namespace LeefiPay\Mpesa\Events;

use LeefiPay\Mpesa\DTOs\WebhookEvent;

class WebhookReceived
{
    public function __construct(
        public WebhookEvent $webhookEvent,
    ) {}
}
