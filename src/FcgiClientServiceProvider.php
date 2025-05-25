<?php

namespace Rizwan\LaravelFcgiClient;

use Illuminate\Support\ServiceProvider;
use Rizwan\LaravelFcgiClient\Client\Client;

class FcgiClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Client::class);
        $this->app->bind(FCGIManager::class);
    }
}
