# Laravel FastCGI Client

A modern, Laravel-style FastCGI client that lets your Laravel application communicate directly with FastCGI-compatible servers like PHP-FPM.

## Acknowledgements

This package is built as a fork of the [fast-cgi-client](https://github.com/hollodotme/fast-cgi-client) library originally authored by **hollodotme**. We’ve adapted and extended it to provide seamless integration with Laravel—many thanks to the original author for creating such a solid foundation.

## What is FastCGI?

FastCGI is a protocol that allows a web server (like Nginx or Apache) to communicate with external applications, typically programming language interpreters like PHP-FPM (PHP FastCGI Process Manager). It's an improvement over the older CGI protocol, offering better performance through persistent processes.

In a traditional PHP web application setup, the web server (e.g., Nginx) receives HTTP requests and forwards them to PHP-FPM via FastCGI, which then executes the PHP scripts and returns the results.

## Where This Package Can Be Used

This package enables Laravel applications to directly communicate with FastCGI servers, which opens up several use cases:

1. **Microservices Architecture**: Directly communicate with other PHP-based microservices without going through HTTP, reducing overhead.

2. **Private API Access**: Access internal APIs on remote servers via FastCGI instead of exposing them through HTTP endpoints.

3. **Cross-Application Communication**: Call scripts on another PHP application from your Laravel app with lower latency than HTTP requests.

4. **Gateway/Proxy Services**: Create gateway services that can route requests to appropriate backend PHP services.

## 🚀 Installation

```bash
  composer require mrizwan/laravel-fcgi-client
```

## 🔧 Configuration

The package automatically registers the service provider and facade in Laravel 9.x and above.

You can use the facade in your code by importing it:

```php
use Rizwan\LaravelFcgiClient\Facades\FCGI;
```

## ✅ Basic Usage

### Simple GET Request

```php
$response = FCGI::withUri('/api/products')
    ->withQuery(['category' => 'electronics'])
    ->get('remote-server.com', '/var/www/public/index.php', 9000);  // Port is optional, defaults to 9000

// Handle the response
$data = $response->json();
$statusCode = $response->status();
```

### POST Request with Data

```php
$response = FCGI::withUri('/api/products')
    ->withPayload([
        'name' => 'New Product',
        'price' => 99.99,
        'category' => 'electronics'
    ])
    ->post('remote-server.com', '/var/www/public/index.php');

// Check if the request was successful
if ($response->successful()) {
    $newProduct = $response->json();
    echo "Created product with ID: " . $newProduct['id'];
}
```

### JSON Request

```php
$response = FCGI::withUri('/api/users')
    ->withPayload([
        'user' => [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]
    ])
    ->json('auth-service.internal', '/var/www/public/index.php');
```

## 📡 Available Methods

### HTTP Methods

- `get(string $host, string $scriptPath, ?int $port = null)`
- `post(string $host, string $scriptPath, ?int $port = null)`
- `put(string $host, string $scriptPath, ?int $port = null)`
- `patch(string $host, string $scriptPath, ?int $port = null)`
- `delete(string $host, string $scriptPath, ?int $port = null)`
- `json(string $host, string $scriptPath, ?int $port = null)` — sends JSON payload with proper headers
- `form(string $host, string $scriptPath, ?int $port = null)` — sends form-encoded data

### Request Configuration

```php
// Add HTTP headers
FCGI::withHeaders([
    'Authorization' => 'Bearer your-token',
    'Accept-Language' => 'en-US'
]);

// Add query parameters (for GET requests)
FCGI::withQuery([
    'page' => 1,
    'limit' => 20,
    'sort' => 'created_at'
]);

// Add request payload (for POST, PUT, PATCH, etc.)
FCGI::withPayload([
    'name' => 'Product Name',
    'description' => 'Product description'
]);

// Set raw body content with content type
FCGI::withBody(
    '{"custom":"json structure"}',
    'application/json'
);

// Set the URI 
FCGI::withUri('/api/v1/products');

// Add custom server parameters that will be available in $_SERVER
FCGI::withServerParams([
    'HTTP_X_CUSTOM_HEADER' => 'Value',
    'SERVER_NAME' => 'custom-server'
]);

// Add custom FastCGI variables
FCGI::withCustomVars([
    'CUSTOM_VAR' => 'custom_value'
]);

// Set connection timeouts
FCGI::timeout(5000);        // Read timeout in milliseconds
FCGI::connectTimeout(3000); // Connect timeout in milliseconds

// Add retry logic
FCGI::retry(
    3,                      // Number of retries
    500,                    // Delay between retries in milliseconds
    function ($exception) { // Optional callback to determine whether to retry
        return !($exception instanceof AuthenticationException);
    }
);
```

## Method Chaining

You can chain multiple methods together for cleaner code:

```php
$response = FCGI::withHeaders(['X-API-Key' => 'secret-key'])
    ->withUri('/api/v1/users')
    ->withQuery(['status' => 'active'])
    ->timeout(10000)
    ->retry(3, 1000)
    ->get('user-service.internal', '/var/www/public/index.php');
```

## 🌐 Response API

The response object provides a rich API similar to Laravel's HTTP client:

```php
$response = FCGI::get(...);

// Status code and state checks
$statusCode = $response->status();          // e.g. 200
$isOk = $response->ok();                    // true if 200
$isUnauthorized = $response->unauthorized(); // true if 401
$isForbidden = $response->forbidden();      // true if 403
$isNotFound = $response->notFound();        // true if 404
$isServerError = $response->serverError();  // true if 5xx
$isSuccessful = $response->successful();    // true if < 400 and no error

// Content access
$body = $response->body();                  // Raw body string
$data = $response->json();                  // Decoded JSON array
$value = $response->json('user.name');      // Access JSON via dot notation
$array = $response->toArray();              // Response as array including timing metrics

// Headers
$contentType = $response->header('Content-Type');  // Get a single header
$allHeaders = $response->getHeaders();            // Get all headers
$hasHeader = $response->hasHeader('X-Custom');    // Check if header exists

// Performance
$duration = $response->getDuration();       // Response read duration in seconds
$connectTime = $response->getConnectDuration(); // TCP connect duration in milliseconds
$writeTime = $response->getWriteDuration();     // Request write duration in milliseconds
$array['connect_duration_ms'];                  // Milliseconds spent connecting
$array['write_duration_ms'];                    // Milliseconds spent writing

// Exception handling
$response->throw();                         // Throws if status >= 400
$response->throwIf($condition);             // Conditional throw 
$response->throwUnless($condition);         // Conditional throw
```

## 🔁 Concurrent Requests with Laravel's Concurrency Facade

For Laravel 11+, you can use Laravel's built-in Concurrency facade to execute multiple FastCGI requests in parallel:

```php
use Illuminate\Support\Facades\Concurrency;

$responses = Concurrency::concurrent([
    'products' => fn() => FCGI::withUri('/api/products')
        ->get('product-service.internal', '/var/www/public/index.php'),
        
    'categories' => fn() => FCGI::withUri('/api/categories')
        ->get('catalog-service.internal', '/var/www/public/index.php'),
        
    'user' => fn() => FCGI::withUri('/api/user')
        ->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->get('user-service.internal', '/var/www/public/index.php'),
]);

// Access responses by key
$products = $responses['products']->json();
$categories = $responses['categories']->json();
$user = $responses['user']->json();
```

## Real-World Examples

### Accessing a Remote API

```php
$response = FCGI::withHeaders([
    'Authorization' => 'Bearer ' . $apiToken,
    'Accept' => 'application/json',
])
->withUri('/api/v1/stats/daily')
->timeout(5000)
->get(
    'api-backend.internal',
    '/var/www/html/api/public/index.php'
);

return $response->json();
```

### Submitting a Form to a Remote Application

```php
$response = FCGI::withPayload([
    'email' => $request->email,
    'name' => $request->name,
    'message' => $request->message,
])
->withUri('/submit-form')
->form(
    'contact-service.internal',
    '/var/www/html/contact/public/index.php'
);

if ($response->successful()) {
    return redirect()->back()->with('success', 'Your message has been sent!');
} else {
    return redirect()->back()->with('error', 'Failed to send message.');
}
```

### Creating a Resource on a Remote Service

```php
$response = FCGI::withPayload([
    'title' => $request->title,
    'content' => $request->content,
    'author_id' => Auth::id(),
])
->withHeaders([
    'X-API-Key' => config('services.blog.api_key'),
])
->withUri('/api/posts')
->json(
    'blog-service.internal',
    '/var/www/html/blog/public/index.php'
);

return response()->json($response->json(), $response->status());
```

### Fetching Data from Multiple Services

```php
use Illuminate\Support\Facades\Concurrency;

$user = auth()->user();

$dashboard = Concurrency::concurrent([
    'profile' => fn() => FCGI::withUri('/api/user/'.$user->id)
        ->get('user-service.internal', '/var/www/user-service/public/index.php'),
        
    'orders' => fn() => FCGI::withUri('/api/orders')
        ->withQuery(['user_id' => $user->id])
        ->get('order-service.internal', '/var/www/order-service/public/index.php'),
        
    'notifications' => fn() => FCGI::withUri('/api/notifications')
        ->withQuery(['user_id' => $user->id, 'unread' => true])
        ->get('notification-service.internal', '/var/www/notification-service/public/index.php'),
]);

return view('dashboard', [
    'profile' => $dashboard['profile']->json(),
    'orders' => $dashboard['orders']->json(),
    'notificationCount' => count($dashboard['notifications']->json('data')),
]);
```

## Error Handling

The package provides several ways to handle errors:

```php
// Using try-catch blocks
try {
    $response = FCGI::get('service.internal', '/path/to/script.php');
    return $response->json();
} catch (\Rizwan\LaravelFcgiClient\Exceptions\ConnectionException $e) {
    // Handle connection errors (e.g., service unreachable)
    Log::error('FastCGI connection failed: ' . $e->getMessage());
    return response()->json(['error' => 'Service unavailable'], 503);
} catch (\Rizwan\LaravelFcgiClient\Exceptions\TimeoutException $e) {
    // Handle timeout errors
    Log::error('FastCGI request timed out: ' . $e->getMessage());
    return response()->json(['error' => 'Request timed out'], 504);
} catch (\Rizwan\LaravelFcgiClient\Exceptions\FastCGIException $e) {
    // Handle all other FastCGI-related errors
    Log::error('FastCGI error: ' . $e->getMessage());
    return response()->json(['error' => 'Internal server error'], 500);
}

// Using the throw method and Laravel's error handling
$posts = FCGI::get('blog-service.internal', '/var/www/blog/public/index.php', ['uri' => '/api/posts'])
    ->throw()  // This will throw an exception if the request fails
    ->json();

// Using conditional throws
$response = FCGI::get('service.internal', '/path/to/script.php');

$response->throwIf(
    $response->status() === 429,
    fn() => new RateLimitException('Too many requests')
);

// Using throwUnless
$response->throwUnless(
    $response->status() === 200,
    fn() => new ServiceException('Expected 200 OK response')
);
```

## Retry Logic

For unstable services or network connections, you can add retry logic:

```php
// Basic retry (3 attempts with 500ms delay)
$response = FCGI::retry(3, 500)
    ->get('flaky-service.internal', '/var/www/public/index.php');

// Conditional retry based on the exception
$response = FCGI::retry(3, 1000, function ($exception) {
    // Only retry for connection and timeout issues, not for auth problems
    return $exception instanceof ConnectionException || 
           $exception instanceof TimeoutException;
})
->withHeaders(['Authorization' => 'Bearer '.$token])
->get('api-service.internal', '/var/www/public/index.php');
```

## 🧪 Testing

Tests are written using [Pest PHP](https://pestphp.com). Run them with:

```bash
./vendor/bin/pest
```

## 📜 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
