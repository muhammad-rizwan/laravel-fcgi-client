<?php

namespace Rizwan\LaravelFcgiClient\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Rizwan\LaravelFcgiClient\LaravelFcgiClientServiceProvider;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelFcgiClientServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'FCGI' => \Rizwan\LaravelFcgiClient\Facades\FCGI::class,
        ];
    }
}
