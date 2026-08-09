<?php

declare(strict_types=1);

namespace LeefiPay\Mpesa;

use Illuminate\Support\ServiceProvider;
use LeefiPay\Mpesa\Contracts\LeefiPayClientInterface;
use LeefiPay\Mpesa\Http\Client;
use LeefiPay\Mpesa\Services\LeefiPayClient;

class LeefiPayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/leefipay.php', 'leefipay');

        $this->app->singleton(Client::class, function ($app) {
            return new Client((array) $app['config']->get('leefipay', []));
        });

        $this->app->singleton(LeefiPayClientInterface::class, function ($app) {
            return new LeefiPayClient($app->make(Client::class));
        });

        $this->app->alias(LeefiPayClientInterface::class, LeefiPayClient::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/leefipay.php' => $this->app->configPath('leefipay.php'),
            ], 'leefipay-config');
        }

        if (config('leefipay.webhook.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/webhooks.php');
        }
    }
}
