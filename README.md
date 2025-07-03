# Laravel FastCGI Client

A modern, Laravel-style FastCGI client that lets your Laravel application communicate directly with FastCGI-compatible servers like PHP-FPM.

The package works similarly to Laravel's HTTP Facade

## Acknowledgements

This package is built as a fork of the [fast-cgi-client](https://github.com/hollodotme/fast-cgi-client) library originally authored by **hollodotme**. We've adapted and extended it to provide seamless integration with Laravel—many thanks to the original author for creating such a solid foundation.

## What is FastCGI?

FastCGI is a protocol that allows a web server (like Nginx or Apache) to communicate with external applications, typically programming language interpreters like PHP-FPM (PHP FastCGI Process Manager). It's an improvement over the older CGI protocol, offering better performance through persistent processes.

In a traditional PHP web application setup, the web server (e.g., Nginx) receives HTTP requests and forwards them to PHP-FPM via FastCGI, which then executes the PHP scripts and returns the results.

## Where This Package Can Be Used

This package enables Laravel applications to directly communicate with PHP-FPM, which opens up several use cases:

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
$response = FCGI::get('remote-server.com/api/products', ['category' => 'electronics']);

// Handle the response
$data = $response->json();
$statusCode = $response->getStatusCode();
```

### POST Request with Data

```php
$response = FCGI::post('123.321.123.321/api/products', [
        'name' => 'New Product',
        'price' => 99.99,
        'category' => 'electronics'
    ]);

// Check if the request was successful
if ($response->successful()) {
    $newProduct = $response->json();
    echo "Created product with ID: " . $newProduct['id'];
}
```

### JSON Request

```php
$response = FCGI::asJson()
    ->post('auth-service.internal/api/users', [
        'user' => [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]
    ]);
```
### Defaults:
* Script path: `/var/www/public/index.php`
* Port: 9000

## 📡 Available Methods

### HTTP Methods

All HTTP methods support Laravel-like signatures with optional data arrays:

- `get(string $url, array $query = [])`
- `post(string $url, array $data = [])`
- `put(string $url, array $data = [])`
- `patch(string $url, array $data = [])`
- `delete(string $url, array $data = [])`

### Request Configuration

```php
// Change FCGI Script Path
FCGI::scriptPath('var/www/html/api/public/index.php');
```

```php
// Change FCGI Port
FCGI::port(9003);
```

```php
// Add HTTP headers
FCGI::withHeaders([
    'Authorization' => 'Bearer your-token',
    'Accept-Language' => 'en-US'
]);

// Add a single header
FCGI::withHeader('X-API-Key', 'secret-key');

// Add query parameters (for GET requests or as method parameter)
FCGI::withQuery([
    'page' => 1,
    'limit' => 20,
    'sort' => 'created_at'
]);

// Add request payload (for POST, PUT, PATCH requests)
FCGI::withPayload([
    'name' => 'Product Name',
    'description' => 'Product description'
]);

// Set raw body content with content type
FCGI::withBody(
    '{"custom":"json structure"}',
    'application/json'
);

// Set URL parameters for template substitution
FCGI::withUrlParameters([
    'userId' => 123,
    'postId' => 456,
    'user' => ['id' => 789] // Supports nested parameters
]);

// Add custom server parameters that will be available in $_SERVER
FCGI::withServerParams([
    'HTTP_X_CUSTOM_HEADER' => 'Value',
    'SERVER_NAME' => 'custom-server'
]);

// Add custom FastCGI variables
FCGI::withCustomVars([
    'CUSTOM_VAR' => 'custom_value'
]);

// Set connection timeouts (in seconds)
FCGI::timeout(30);        // Read timeout
FCGI::connectTimeout(5);  // Connect timeout

// Add retry logic
FCGI::retry(
    3,                      // Number of retries
    500,                    // Delay between retries in milliseconds
    function ($exception) { // Optional callback to determine whether to retry
        return !($exception instanceof AuthenticationException);
    }
);
```

### Content Type Configuration

```php
// Configure request to send JSON payload
FCGI::asJson()
    ->post('service.com/doSomething', ['key' => 'value']);

// Configure request to send form-encoded data
FCGI::asForm()
    ->post('service.com/doSomethingElse', ['key' => 'value']);

// Set Accept header to JSON
FCGI::acceptJson();

// Set Accept header to specific content type
FCGI::accept('application/xml');

// Set custom content type
FCGI::contentType('application/vnd.api+json');
```

### Authentication

```php
// Bearer token authentication
FCGI::withToken('your-jwt-token')
    ->get('api.example.com/protected/endpoint');

// Custom token type
FCGI::withToken('api-key-123', 'ApiKey')
    ->get('api.example.com/protected/endpoint');

// Basic authentication
FCGI::withBasicAuth('username', 'password')
    ->get('api.example.com/protected/endpoint');

// Custom User-Agent
FCGI::withUserAgent('MyApp/1.0')
    ->get('api.example.com/endpoint');
```

## Method Chaining

You can chain multiple methods together for cleaner code:

```php
$response = FCGI::withHeaders(['X-API-Key' => 'secret-key'])
    ->withUrlParameters(['userId' => 123])
    ->withQuery(['include' => 'profile'])
    ->timeout(10)
    ->retry(3, 1000)
    ->get('user-service.internal/api/v1/users/{userId}');
```

## 🔗 URL Templates

The package supports URL templates with parameter substitution:

```php
// Simple parameter substitution
$response = FCGI::withUrlParameters(['id' => 123])
    ->get('api.example.com/api/users/{id}');
// Results in: /api/users/123

// Nested parameter support
$response = FCGI::withUrlParameters([
        'user' => ['id' => 123],
        'post' => ['id' => 456]
    ])
    ->get('api.example.com/api/users/{user.id}/posts/{post.id}');
// Results in: /api/users/123/posts/456

// Parameters not found remain as placeholders
$response = FCGI::withUrlParameters(['existing' => 'value'])
    ->get('api.example.com/api/{missing}/endpoint');
// Results in: /api/{missing}/endpoint
```

## 🌐 Response API

The response object provides a rich API similar to Laravel's HTTP client:

```php
$response = FCGI::get('api.example.com/endpoint');

// Status code and state checks
$statusCode = $response->getStatusCode();       // e.g. 200
$isOk = $response->ok();                       // true if 200
$isUnauthorized = $response->unauthorized();   // true if 401
$isForbidden = $response->forbidden();         // true if 403
$isNotFound = $response->notFound();           // true if 404
$isServerError = $response->serverError();     // true if 5xx
$isSuccessful = $response->successful();       // true if < 400 and no error

// Content access
$body = $response->body();                     // Raw body string
$data = $response->json();                     // Decoded JSON array
$value = $response->json('user.name');         // Access JSON via dot notation
$array = $response->toArray();                 // Response as array including timing metrics

// Headers
$contentType = $response->header('Content-Type');  // Get a single header
$allHeaders = $response->getHeaders();            // Get all headers
$hasHeader = $response->hasHeader('X-Custom');    // Check if header exists

// Performance
$duration = $response->getDuration();             // Response read duration in seconds
$connectTime = $response->getConnectDuration();   // TCP connect duration in milliseconds
$writeTime = $response->getWriteDuration();       // Request write duration in milliseconds
$attempts = $response->getAttempts();             // Number of retry attempts made

// Exception handling
$response->throw();                               // Throws if status >= 400
$response->throwIf($condition);                   // Conditional throw 
$response->throwUnless($condition);               // Conditional throw
```

## 🔁 Concurrent Requests with Laravel's Concurrency Facade

For Laravel 11+, you can use Laravel's built-in Concurrency facade to execute multiple FastCGI requests in parallel:

```php
use Illuminate\Support\Facades\Concurrency;

$responses = Concurrency::concurrent([
    'products' => fn() => FCGI::get('product-service.internal/api/products'),
    'categories' => fn() => FCGI::get('catalog-service.internal/api/categories'),
    'user' => fn() => FCGI::withToken($token)->get('user-service.internal/api/user'),
]);

// Access responses by key
$products = $responses['products']->json();
$categories = $responses['categories']->json();
$user = $responses['user']->json();
```

## Moore Examples

### Accessing a Remote API with URL Templates

```php
$response = FCGI::withToken($apiToken)
    ->acceptJson()
    ->withUrlParameters(['userId' => auth()->id()])
    ->timeout(5)
    ->scriptPath('/var/www/html/api/public/index.php')
    ->get('api-backend.internal/api/v1/users/{userId}/stats/daily');

return $response->json();
```

### Submitting a Form to a Remote Application

```php
$response = FCGI::asForm()
    ->scriptPath('/var/www/html/contact/public/index.php')
    ->post('contact-service.internal/submit-form', [
        'email' => $request->email,
        'name' => $request->name,
        'message' => $request->message,
    ]);

if ($response->successful()) {
    return redirect()->back()->with('success', 'Your message has been sent!');
} else {
    return redirect()->back()->with('error', 'Failed to send message.');
}
```

### Creating a Resource on a Remote Service

```php
$response = FCGI::withHeaders([
        'X-API-Key' => config('services.blog.api_key'),
    ])
    ->asJson()
    ->scriptPath('/var/www/html/blog/public/index.php')
    ->post('blog-service.internal/api/posts', [
        'title' => $request->title,
        'content' => $request->content,
        'author_id' => Auth::id(),
    ]);

return response()->json($response->json(), $response->getStatusCode());
```

### RESTful API with URL Templates

```php
// GET /api/users/123/posts/456/comments
$response = FCGI::withUrlParameters([
        'userId' => $user->id,
        'postId' => $post->id
    ])
    ->scriptPath('/var/www/blog/public/index.php')
    ->get('blog-service.internal/api/users/{userId}/posts/{postId}/comments', ['include' => 'author,replies']);

// PUT /api/users/123/profile
$response = FCGI::withUrlParameters(['userId' => $user->id])
    ->asJson()
    ->scriptPath('/var/www/user/public/index.php')
    ->put('user-service.internal/api/users/{userId}/profile', [
        'name' => $request->name,
        'bio' => $request->bio
    ]);

// DELETE /api/posts/456
$response = FCGI::withUrlParameters(['postId' => $post->id])
    ->scriptPath('/var/www/blog/public/index.php')
    ->delete('blog-service.internal/api/posts/{postId}');
```

### Fetching Data from Multiple Services

```php
use Illuminate\Support\Facades\Concurrency;

$user = auth()->user();

$dashboard = Concurrency::concurrent([
    'profile' => fn() => FCGI::scriptPath('/var/www/user-service/public/index.php')
        ->withUrlParameters(['userId' => $user->id])
        ->get('user-service.internal/api/users/{userId}'),
        
    'orders' => fn() => FCGI::withUrlParameters(['userId' => $user->id])
        ->withQuery(['status' => 'active'])
        ->scriptPath('/var/www/order-service/public/index.php')
        ->get('order-service.internal/api/users/{userId}/orders'),
        
    'notifications' => fn() => FCGI::withUrlParameters(['userId' => $user->id])
        ->scriptPath('/var/www/notification-service/public/index.php')
        ->port(12345)
        ->get('notification-service.internal/api/users/{userId}/notifications', ['unread' => true]),
]);

return view('dashboard', [
    'profile' => $dashboard['profile']->json(),
    'orders' => $dashboard['orders']->json(),
    'notificationCount' => count($dashboard['notifications']->json('data')),
]);
```

## Smart Content Type Defaults

The package intelligently chooses content types based on the HTTP method:

```php
// POST requests default to form-encoded data
FCGI::post('service.com', ['key' => 'value']);
// Content-Type: application/x-www-form-urlencoded

// PUT/PATCH requests default to JSON
FCGI::put('service.com', ['key' => 'value']);
// Content-Type: application/json

// Override defaults with explicit configuration
FCGI::asJson()->post('service.com', ['key' => 'value']);
// Content-Type: application/json

FCGI::asForm()->put('service.com', ['key' => 'value']);
// Content-Type: application/x-www-form-urlencoded
```

## Error Handling

The package provides several ways to handle errors:

```php
// Using try-catch blocks
try {
    $response = FCGI::get('service.internal');
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
$posts = FCGI::get('blog-service.internal', '/var/www/blog/public/index.php')
    ->throw()  // This will throw an exception if the request fails
    ->json();

// Using conditional throws
$response = FCGI::get('service.internal');

$response->throwIf(
    $response->getStatusCode() === 429,
    fn() => new RateLimitException('Too many requests')
);

// Using throwUnless
$response->throwUnless(
    $response->getStatusCode() === 200,
    fn() => new ServiceException('Expected 200 OK response')
);
```

## Retry Logic

For unstable services or network connections, you can add retry logic with intelligent defaults:

```php
// Basic retry (3 attempts with 500ms delay)
$response = FCGI::retry(3, 500)
    ->get('flaky-service.internal');

// By default, retries happen on:
// - Connection failures and timeouts (always)
// - Server errors (5xx status codes)
// - NOT on client errors (4xx status codes)

// Custom retry logic based on the exception or response
$response = FCGI::retry(3, 1000, function ($exceptionOrResponse, $request) {
    // Only retry for connection and timeout issues
    if ($exceptionOrResponse instanceof \Throwable) {
        return $exceptionOrResponse instanceof ConnectionException || 
               $exceptionOrResponse instanceof TimeoutException;
    }
    
    // For responses, retry on server errors but not client errors
    if ($exceptionOrResponse instanceof Response) {
        return $exceptionOrResponse->serverError();
    }
    
    return false;
})
->withToken($token)
->get('api-service.internal');

// Check retry attempts
$response = FCGI::retry(3)->get('service.com');
echo "Request took {$response->getAttempts()} attempts";
```

## 🧪 Testing

Tests are written using [Pest PHP](https://pestphp.com). Run them with:

```bash
./vendor/bin/pest
```

## 📜 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
