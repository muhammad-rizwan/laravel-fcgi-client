<?php

namespace Rizwan\LaravelFcgiClient\Facades;

use Illuminate\Support\Facades\Facade;
use Rizwan\LaravelFcgiClient\FCGIManager;

/**
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager connect(string $host, int $port = 9000)
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager connectUnix(string $socketPath)
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager timeout(int $milliseconds)
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager connectTimeout(int $milliseconds)
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager readTimeout(int $milliseconds)
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager withServerParam(string $name, string $value)
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager withCustomVar(string $name, mixed $value)
 * @method static \Rizwan\LaravelFcgiClient\Responses\Response get(string $path, array $query = [])
 * @method static \Rizwan\LaravelFcgiClient\Responses\Response post(string $path, array $data = [])
 * @method static \Rizwan\LaravelFcgiClient\Responses\Response put(string $path, array $data = [])
 * @method static \Rizwan\LaravelFcgiClient\Responses\Response patch(string $path, array $data = [])
 * @method static \Rizwan\LaravelFcgiClient\Responses\Response delete(string $path)
 * @method static \Rizwan\LaravelFcgiClient\Responses\Response json(string $path, array $data)
 * @method static array pool(\Closure $callback)
 */
class FCGI extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FCGIManager::class;
    }
}
