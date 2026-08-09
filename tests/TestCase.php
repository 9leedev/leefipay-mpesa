<?php

declare(strict_types=1);

namespace LeefiPay\Mpesa\Tests;

use LeefiPay\Mpesa\LeefiPayServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LeefiPayServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'LeefiPay' => \LeefiPay\Mpesa\Facades\LeefiPay::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('leefipay.base_url', 'https://api.example.test');
        $app['config']->set('leefipay.api_key', 'test-token');
        $app['config']->set('leefipay.environment', 'sandbox');
        $app['config']->set('leefipay.webhook.enabled', true);
        $app['config']->set('leefipay.webhook.secret', 'whsec_test_secret');
        $app['config']->set('leefipay.webhook.path', 'leefipay/webhooks/mpesa');
        $app['config']->set('leefipay.retry.enabled', false);
        $app['config']->set('leefipay.logging.enabled', false);
    }
}
