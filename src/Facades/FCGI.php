<?php

namespace Rizwan\LaravelFcgiClient\Facades;

use Illuminate\Support\Facades\Facade;
use Rizwan\LaravelFcgiClient\FCGIManager;

/**
 * @method static \Rizwan\LaravelFcgiClient\Responses\Response get(string $url, array $options = [])
 * @method static \Rizwan\LaravelFcgiClient\Responses\Response post(string $url, array $options = [])
 * @method static \Rizwan\LaravelFcgiClient\Responses\Response put(string $url, array $options = [])
 * @method static \Rizwan\LaravelFcgiClient\Responses\Response patch(string $url, array $options = [])
 * @method static \Rizwan\LaravelFcgiClient\Responses\Response delete(string $url, array $options = [])
 * @method static \Rizwan\LaravelFcgiClient\Responses\Response json(string $url, array $options = [])
 * @method static array pool(\Closure $callback)
 */
class FCGI extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FCGIManager::class;
    }
}
