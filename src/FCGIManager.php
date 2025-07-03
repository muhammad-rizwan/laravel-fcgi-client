<?php

namespace Rizwan\LaravelFcgiClient;

use Closure;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\UriTemplate\UriTemplate;
use Illuminate\Support\Str;
use Rizwan\LaravelFcgiClient\Client\Client;
use Rizwan\LaravelFcgiClient\Connections\NetworkConnection;
use Rizwan\LaravelFcgiClient\Enums\RequestMethod;
use Rizwan\LaravelFcgiClient\Requests\Request;
use Rizwan\LaravelFcgiClient\Requests\RequestBuilder;
use Rizwan\LaravelFcgiClient\Responses\Response;
use Throwable;

class FCGIManager
{
    private array $urlParameters = [];
    private array $headers = [];
    private array $serverParams = [];
    private array $customVars = [];
    private array $query = [];
    private array $payload = [];
    private ?string $rawBody = null;
    private string $rawBodyType = 'text/plain';
    private int $connectTimeout = 5;
    private int $readTimeout = 30;
    private int $maxRetries = 0;
    private int $retryDelayMs = 0;
    private ?Closure $retryWhen = null;
    private bool $asJsonRequest = false;
    private bool $asFormRequest = false;

    /**
     * Create a new FastCGI Manager instance.
     */
    public function __construct(
        private string $scriptPath = '/var/www/public/index.php',
        private int $port = 9000,
        private readonly Client $client,
    ) {
    }

    // ========================================
    // Core HTTP Methods
    // ========================================

    /**
     * Send a GET request to the FastCGI server.
     */
    public function get(string $url, array $query = []): Response
    {
        if (!empty($query)) {
            $this->withQuery($query);
        }
        return $this->sendRequest(RequestMethod::GET, $url);
    }

    /**
     * Send a HEAD request to the FastCGI server.
     */
    public function head(string $url, array $query = []): Response
    {
        if (!empty($query)) {
            $this->withQuery($query);
        }
        return $this->sendRequest(RequestMethod::HEAD, $url);
    }

    /**
     * Send a POST request to the FastCGI server.
     */
    public function post(string $url, array $data = []): Response
    {
        if (!empty($data)) {
            $this->withPayload($data);
        }
        return $this->sendRequest(RequestMethod::POST, $url);
    }

    /**
     * Send a PATCH request to the FastCGI server.
     */
    public function patch(string $url, array $data = []): Response
    {
        if (!empty($data)) {
            $this->withPayload($data);
        }
        return $this->sendRequest(RequestMethod::PATCH, $url);
    }

    /**
     * Send a PUT request to the FastCGI server.
     */
    public function put(string $url, array $data = []): Response
    {
        if (!empty($data)) {
            $this->withPayload($data);
        }
        return $this->sendRequest(RequestMethod::PUT, $url);
    }

    /**
     * Send a DELETE request to the FastCGI server.
     */
    public function delete(string $url, array $data = []): Response
    {
        if (!empty($data)) {
            // DELETE can have data in query or body - let's default to query for RESTful APIs
            if ($this->asJsonRequest || $this->asFormRequest || !empty($this->rawBody)) {
                $this->withPayload($data);
            } else {
                $this->withQuery($data);
            }
        }
        return $this->sendRequest(RequestMethod::DELETE, $url);
    }

    // ========================================
    // Request Configuration Methods
    // ========================================

    /**
     * @param string $scriptPath
     * @return self
     */
    public function scriptPath(string $scriptPath): self
    {
        $this->scriptPath = $scriptPath;
        return $this;
    }

    /**
     * @param int $port
     * @return self
     */
    public function port(int $port): self
    {
        $this->port = $port;
        return $this;
    }

    /**
     * Set HTTP headers for the request.
     */
    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Set a single HTTP header.
     */
    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set raw body content with a specific content type.
     */
    public function withBody(string $content, string $contentType = 'text/plain'): self
    {
        $this->rawBody = $content;
        $this->rawBodyType = $contentType;
        return $this;
    }

    /**
     * Set a Bearer token for authentication.
     */
    public function withToken(string $token, string $type = 'Bearer'): self
    {
        return $this->withHeader('Authorization', "{$type} {$token}");
    }

    /**
     * Set HTTP Basic authentication.
     */
    public function withBasicAuth(string $username, string $password): self
    {
        $credentials = base64_encode("{$username}:{$password}");
        return $this->withHeader('Authorization', "Basic {$credentials}");
    }

    /**
     * Set a custom User-Agent header.
     */
    public function withUserAgent(string $userAgent): self
    {
        return $this->withHeader('User-Agent', $userAgent);
    }

    /**
     * Set URL parameters for template substitution.
     * Supports both flat and nested parameters.
     */
    public function withUrlParameters(array $parameters): self
    {
        $this->urlParameters = array_merge($this->urlParameters, $parameters);
        return $this;
    }

    /**
     * Set custom server parameters.
     */
    public function withServerParams(array $params): self
    {
        $this->serverParams = array_merge($this->serverParams, $params);
        return $this;
    }

    /**
     * Set custom FastCGI variables.
     */
    public function withCustomVars(array $vars): self
    {
        $this->customVars = array_merge($this->customVars, $vars);
        return $this;
    }

    /**
     * Set query parameters for the request.
     */
    public function withQuery(array $query): self
    {
        $this->query = array_merge($this->query, $query);
        return $this;
    }

    /**
     * Set the request payload (for POST, PUT, PATCH requests).
     */
    public function withPayload(array $data): self
    {
        $this->payload = array_merge($this->payload, $data);
        return $this;
    }

    // ========================================
    // Content Type Methods
    // ========================================

    /**
     * Configure request to send JSON payload.
     */
    public function asJson(): self
    {
        $this->asJsonRequest = true;
        $this->asFormRequest = false;
        return $this->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json');
    }

    /**
     * Configure request to send form-encoded payload.
     */
    public function asForm(): self
    {
        $this->asFormRequest = true;
        $this->asJsonRequest = false;
        return $this->withHeader('Content-Type', 'application/x-www-form-urlencoded');
    }

    /**
     * Set Accept header to application/json.
     */
    public function acceptJson(): self
    {
        return $this->withHeader('Accept', 'application/json');
    }

    /**
     * Set Accept header to a specific content type.
     */
    public function accept(string $contentType): self
    {
        return $this->withHeader('Accept', $contentType);
    }

    /**
     * Set a custom content type.
     */
    public function contentType(string $contentType): self
    {
        return $this->withHeader('Content-Type', $contentType);
    }

    // ========================================
    // Connection & Retry Methods
    // ========================================

    /**
     * Set the read timeout for the request (in seconds).
     */
    public function timeout(int $seconds): self
    {
        $this->readTimeout = $seconds;
        return $this;
    }

    /**
     * Set the connection timeout for the request (in seconds).
     */
    public function connectTimeout(int $seconds): self
    {
        $this->connectTimeout = $seconds;
        return $this;
    }

    /**
     * Configure retry logic for failed requests.
     */
    public function retry(int $times, int $sleepMilliseconds = 0, ?callable $when = null): self
    {
        $this->maxRetries = $times;
        $this->retryDelayMs = $sleepMilliseconds;
        $this->retryWhen = $when instanceof Closure ? $when : ($when ? Closure::fromCallable($when) : null);
        return $this;
    }

    // ========================================
    // Private Helper Methods
    // ========================================

    /**
     * Determine if the request should be retried based on response or exception.
     */
    private function shouldRetry(?Response $response, ?Throwable $exception, $request): bool
    {
        // If custom retry logic is provided, use it
        if ($this->retryWhen !== null) {
            return ($this->retryWhen)($exception ?? $response, $request);
        }

        // Default behavior - always retry on exceptions (network failures, timeouts, etc.)
        if ($exception !== null) {
            return true;
        }

        // Retry on server errors (5xx) but not client errors (4xx)
        if ($response !== null) {
            $statusCode = $response->getStatusCode();
            return $statusCode >= 500;
        }

        return false;
    }

    /**
     * Send a request to the FastCGI server.
     */
    private function sendRequest(RequestMethod $method, string $url): Response
    {
        if (!Str::startsWith($url, ['tcp://'])) {
            $url = 'tcp://' . trim($url, '/');
        }

        // Process and set REQUEST_URI with URL template substitution
        $uri = UriTemplate::expand($url, $this->urlParameters);

        $processedUri = Utils::uriFor($uri);

        $builder = (new RequestBuilder())
            ->path($this->scriptPath)
            ->method($method)
            ->host($processedUri->getHost())
            ->requestUri($processedUri->getPath())
            ->withHeaders($this->headers);

        // Handle request body for POST/PUT/PATCH
        if (in_array($method, [RequestMethod::POST, RequestMethod::PUT, RequestMethod::PATCH])) {
            if (!empty($this->rawBody)) {
                $builder->withBody($this->rawBody, $this->rawBodyType);
            } elseif (!empty($this->payload)) {
                if ($this->asJsonRequest) {
                    $builder->acceptJson()->json($this->payload);
                } elseif ($this->asFormRequest) {
                    $builder->asForm()->formData($this->payload);
                } else {
                    // Smart defaults: POST uses form, PUT/PATCH use JSON
                    if ($method === RequestMethod::POST) {
                        $builder->asForm()->formData($this->payload);
                    } else {
                        $builder->acceptJson()->json($this->payload);
                    }
                }
            }
        }

        // Handle query parameters for GET/DELETE or when explicitly set
        if (($method === RequestMethod::GET || $method === RequestMethod::DELETE) && !empty($this->query)) {
            $builder->query($this->query);
        }

        // Handle DELETE with payload (less common but valid)
        if ($method === RequestMethod::DELETE && !empty($this->payload) && ($this->asJsonRequest || $this->asFormRequest || !empty($this->rawBody))) {
            if ($this->asJsonRequest) {
                $builder->acceptJson()->json($this->payload);
            } elseif ($this->asFormRequest) {
                $builder->asForm()->formData($this->payload);
            }
        }

        // Apply additional server parameters
        foreach ($this->serverParams as $key => $value) {
            $builder = $builder->withServerParam($key, $value);
        }

        // Apply custom FastCGI variables
        foreach ($this->customVars as $key => $value) {
            $builder = $builder->withCustomVar($key, $value);
        }

        return $this->executeWithRetry($builder->build());
    }

    /**
     * Execute request with retry logic.
     */
    private function executeWithRetry(Request $request): Response
    {
        $attempts = 0;
        $connection = new NetworkConnection(
            $request->host,
            $this->port,
            $this->connectTimeout * 1000, // Convert to milliseconds
            $this->readTimeout * 1000     // Convert to milliseconds
        );

        do {
            $attempts++;

            try {
                $response = $this->client->sendRequest($connection, $request);
                $response->setAttempts($attempts);

                // Check if we should retry based on the response
                if ($attempts <= $this->maxRetries && $this->shouldRetry($response, null, $request)) {
                    if ($this->retryDelayMs > 0) {
                        usleep($this->retryDelayMs * 1000);
                    }
                    continue;
                }

                return $response;

            } catch (Throwable $e) {
                // Check if we should retry based on the exception
                if ($attempts <= $this->maxRetries && $this->shouldRetry(null, $e, $request)) {
                    if ($this->retryDelayMs > 0) {
                        usleep($this->retryDelayMs * 1000);
                    }
                    continue;
                }

                // No more retries - create a failed response instead of throwing
                $errorBody = json_encode([
                    'error' => $e->getMessage(),
                    'type' => 'connection_failure',
                    'trace' => $e->getTraceAsString()
                ]);

                // Format as FCGI response with 503 status
                $errorOutput = "Status: 503 Service Unavailable\r\n" .
                    "Content-Type: application/json\r\n" .
                    "\r\n" .
                    $errorBody;

                $response = new Response(
                    output: $errorOutput,
                    error: $e->getMessage(),
                    duration: 0.0,
                    connectDuration: 0.0,
                    writeDuration: 0.0
                );

                $response->setAttempts($attempts);
                return $response;
            }

        } while ($attempts <= $this->maxRetries);

        return $response ?? throw new \RuntimeException('Unexpected state in retry logic');
    }
}
