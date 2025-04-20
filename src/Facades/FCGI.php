<?php

namespace Rizwan\LaravelFcgiClient\Facades;

use Illuminate\Support\Facades\Facade;
use Rizwan\LaravelFcgiClient\FCGIManager;

/**
 * Laravel Facade for FCGIManager.
 *
 * Provides static access to the FastCGI client manager methods.
 *
 * @method static \Rizwan\LaravelFcgiClient\Responses\Response get(string $host, string $scriptPath, array $options = [], ?int $port = null)
 * @method static \Rizwan\LaravelFcgiClient\Responses\Response post(string $host, string $scriptPath, array $options = [], ?int $port = null)
 * @method static \Rizwan\LaravelFcgiClient\Responses\Response put(string $host, string $scriptPath, array $options = [], ?int $port = null)
 * @method static \Rizwan\LaravelFcgiClient\Responses\Response patch(string $host, string $scriptPath, array $options = [], ?int $port = null)
 * @method static \Rizwan\LaravelFcgiClient\Responses\Response delete(string $host, string $scriptPath, array $options = [], ?int $port = null)
 * @method static \Rizwan\LaravelFcgiClient\Responses\Response json(string $host, string $scriptPath, array $options = [], ?int $port = null)
 * @method static array pool(\Closure $callback)
 */
final class FCGI extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return FCGIManager::class;
    }
}
