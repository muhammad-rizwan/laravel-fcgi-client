<?php

namespace Rizwan\LaravelFcgiClient\Facades;

use Illuminate\Support\Facades\Facade;
use Rizwan\LaravelFcgiClient\FCGIManager;

/**
 * Laravel Facade for FCGIManager - Complete Laravel HTTP Client API for FastCGI.
 *
 * Provides a Laravel HTTP client-like interface for communicating with FastCGI servers,
 * with full support for URL templates, content types, authentication, and retry logic.
 *
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager withHeaders(array $headers)
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager withHeader(string $name, string $value)
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager withBody(string $content, string $contentType = 'text/plain')
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager withToken(string $token, string $type = 'Bearer')
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager withBasicAuth(string $username, string $password)
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager withUserAgent(string $userAgent)
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager withUri(string $uri)
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager withUrlParameters(array $parameters)
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager withServerParams(array $params)
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager withCustomVars(array $vars)
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager withQuery(array $query)
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager withPayload(array $data)
 *
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager asJson()
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager asForm()
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager acceptJson()
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager accept(string $contentType)
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager contentType(string $contentType)
 *
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager timeout(int $seconds)
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager connectTimeout(int $seconds)
 * @method static \Rizwan\LaravelFcgiClient\FCGIManager retry(int $times, int $sleepMilliseconds = 0, ?callable $when = null)
 *
 * @method static \Rizwan\LaravelFcgiClient\Responses\Response get(string $host, string $scriptPath, array $query = [], ?int $port = null)
 * @method static \Rizwan\LaravelFcgiClient\Responses\Response post(string $host, string $scriptPath, array $data = [], ?int $port = null)
 * @method static \Rizwan\LaravelFcgiClient\Responses\Response put(string $host, string $scriptPath, array $data = [], ?int $port = null)
 * @method static \Rizwan\LaravelFcgiClient\Responses\Response patch(string $host, string $scriptPath, array $data = [], ?int $port = null)
 * @method static \Rizwan\LaravelFcgiClient\Responses\Response delete(string $host, string $scriptPath, array $data = [], ?int $port = null)
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
