<?php

namespace Rizwan\LaravelFcgiClient;

use Illuminate\Support\ServiceProvider;
use Rizwan\LaravelFcgiClient\Client\Client;

class FcgiClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Client::class, function () {
            return new Client;
        });

        $this->app->singleton(FCGIManager::class, function ($app) {
            return new FCGIManager(
                $app->make(Client::class)
            );
        });
    }
}
