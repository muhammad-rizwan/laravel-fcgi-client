
# Laravel FastCGI Client

A modern, Laravel-style FastCGI client that lets your Laravel app communicate with FastCGI-compatible servers like PHP-FPM.

## 🚀 Installation

```bash
composer require rizwan/laravel-fcgi-client
```

## 📦 Service Provider & Facade

The package registers itself automatically in Laravel 11+ via the `extra.laravel` section in `composer.json`.

You can also use the facade:

```php
use FCGI;
```

## ✅ Basic Usage

```php
$response = FCGI::withHeaders([
    'Authorization' => 'Bearer ...',
])->withQuery([
    'sort' => 'price',
])->withUri('/api/v1/games')
  ->get('127.0.0.1', '/var/www/public/index.php');
```

## 📡 Available Methods

### HTTP Methods

- `get($host, $scriptPath, $port = null)`
- `post($host, $scriptPath, $port = null)`
- `put($host, $scriptPath, $port = null)`
- `patch($host, $scriptPath, $port = null)`
- `delete($host, $scriptPath, $port = null)`
- `json($host, $scriptPath, $port = null)` — sets JSON content headers
- `form($host, $scriptPath, $port = null)` — sets `application/x-www-form-urlencoded`

### Request Configuration

```php
FCGI::withHeaders([...]);       // Sets headers
FCGI::withToken('abc');         // Sets Bearer token
FCGI::withBasicAuth('u', 'p');  // Sets Authorization header
FCGI::withUserAgent('Custom');  // Sets User-Agent
FCGI::withPayload([...]);       // Data for form or JSON
FCGI::withBody('raw', 'text/plain'); // Raw body
FCGI::withQuery([...]);         // Query string
FCGI::withServerParams([...]);  // $_SERVER params
FCGI::withCustomVars([...]);    // FastCGI custom vars
FCGI::withUri('/path');         // Sets REQUEST_URI and SCRIPT_NAME
FCGI::timeout(3000);            // Read timeout (ms)
FCGI::connectTimeout(3000);     // Connect timeout (ms)
FCGI::retry(3, 500);            // Retry 3 times, 500ms delay
```

## 🌐 Response API

```php
$response = FCGI::get(...);

$response->status();                // e.g. 200
$response->ok();                    // true if 200
$response->unauthorized();         // true if 401
$response->forbidden();            // true if 403
$response->notFound();             // true if 404
$response->serverError();          // true if 5xx
$response->successful();           // true if < 400 and no error

$response->body();                 // raw body string
$response->json();                 // decoded array from JSON body
$response->json('user.name');      // access JSON via dot notation
$response->toArray();              // summary array
$response->header('Content-Type'); // single header line
$response->getHeaders();           // full headers
$response->hasHeader('X-Foo');     // check if header exists
$response->getDuration();          // in seconds
$response->throw();                // throws if status >= 400
$response->throwIf(...);          // conditional throw
$response->throwUnless(...);      // conditional throw
```

## 🔁 Pooling / Concurrent Requests

```php
$results = FCGI::pool(function (FCGIManager $fcgi) {
    return [
        'user' => fn() => $fcgi->withUri('/api/user')->get('localhost', '/var/www/public/index.php'),
        'posts' => fn() => $fcgi->withUri('/api/posts')->get('localhost', '/var/www/public/index.php'),
    ];
});
```

## 🧪 Testing

Tests are written using [Pest PHP](https://pestphp.com). Run them with:

```bash
./vendor/bin/pest
```

## 📜 License

MIT
