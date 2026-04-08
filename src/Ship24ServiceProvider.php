<?php

declare(strict_types=1);

namespace Graystack\Ship24;

use Graystack\Ship24\Connectors\Ship24Connector;
use Illuminate\Support\ServiceProvider;

class Ship24ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ship24.php', 'ship24');

        $this->app->singleton(Ship24Connector::class, function () {
            $apiKey = (string) config('ship24.api_key', '');

            if (empty($apiKey)) {
                throw new \RuntimeException(
                    'Ship24 API key is not configured. Set SHIP24_API_KEY in your .env file.'
                );
            }

            return new Ship24Connector(
                apiKey: $apiKey,
                baseUrl: (string) config('ship24.base_url', 'https://api.ship24.com/public/v1'),
            );
        });

        $this->app->singleton(Ship24Client::class, fn ($app) => new Ship24Client(
            connector: $app->make(Ship24Connector::class),
        ));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/ship24.php' => config_path('ship24.php'),
            ], 'ship24-config');
        }
    }
}
