<?php

namespace Rizwan\LaravelFcgiClient;

use Closure;
use Rizwan\LaravelFcgiClient\Client\Client;
use Rizwan\LaravelFcgiClient\Connections\NetworkConnection;
use Rizwan\LaravelFcgiClient\Enums\RequestMethod;
use Rizwan\LaravelFcgiClient\Requests\RequestBuilder;
use Rizwan\LaravelFcgiClient\Responses\Response;
use Throwable;

class FCGIManager
{
    private ?NetworkConnection $connection = null;
    private string $scriptPath = '';
    private string $uri = '';
    private array $headers = [];
    private array $serverParams = [];
    private array $customVars = [];
    private array $query = [];
    private array $payload = [];
    private ?string $rawBody = null;
    private string $rawBodyType = 'text/plain';
    private int $connectTimeout = 5000;
    private int $readTimeout = 5000;
    private int $maxRetries = 0;
    private int $retryDelayMs = 0;
    private ?Closure $retryWhen = null;

    /**
     * Create a new FastCGI Manager instance.
     *
     * @param Client $client The FastCGI client implementation
     */
    public function __construct(
        private readonly Client $client = new Client,
    ) {
    }

    /**
     * Set HTTP headers for the request.
     *
     * @param array $headers Associative array of header names and values
     * @return $this
     */
    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    /**
     * Set query parameters for the request.
     *
     * @param array $query Associative array of query parameters
     * @return $this
     */
    public function withQuery(array $query): self
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Set the request payload (for POST, PUT, PATCH, JSON requests).
     *
     * @param array $data The data to send in the request body
     * @return $this
     */
    public function withPayload(array $data): self
    {
        $this->payload = $data;

        return $this;
    }

    /**
     * Set a raw body content with a specific content type.
     *
     * @param string $body The raw body content
     * @param string $type The content type (MIME type)
     * @return $this
     */
    public function withBody(string $body, string $type = 'text/plain'): self
    {
        $this->rawBody = $body;
        $this->rawBodyType = $type;

        return $this;
    }

    /**
     * Set the request URI.
     *
     * This sets the REQUEST_URI server parameter for the FastCGI request.
     *
     * @param string $uri The URI path for the request
     * @return $this
     */
    public function withUri(string $uri): self
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * Set custom server parameters.
     *
     * These will be available in the $_SERVER superglobal in the PHP script.
     *
     * @param array $params Associative array of server parameters
     * @return $this
     */
    public function withServerParams(array $params): self
    {
        $this->serverParams = $params;

        return $this;
    }

    /**
     * Set custom FastCGI variables.
     *
     * @param array $vars Associative array of custom FastCGI variables
     * @return $this
     */
    public function withCustomVars(array $vars): self
    {
        $this->customVars = $vars;

        return $this;
    }

    /**
     * Set a Bearer token for authentication.
     *
     * @param string $token The authentication token
     * @return $this
     */
    public function withToken(string $token): self
    {
        return $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ]);
    }

    /**
     * Set HTTP Basic authentication.
     *
     * @param string $username The username
     * @param string $password The password
     * @return $this
     */
    public function withBasicAuth(string $username, string $password): self
    {
        $credentials = base64_encode("{$username}:{$password}");

        return $this->withHeaders([
            'Authorization' => "Basic {$credentials}",
        ]);
    }

    /**
     * Set a custom User-Agent header.
     *
     * @param string $agent The User-Agent string
     * @return $this
     */
    public function withUserAgent(string $agent): self
    {
        return $this->withHeaders([
            'User-Agent' => $agent,
        ]);
    }

    /**
     * Set the read timeout for the request.
     *
     * @param int $ms Timeout in milliseconds
     * @return $this
     */
    public function timeout(int $ms): self
    {
        $this->readTimeout = $ms;

        return $this;
    }

    /**
     * Set the connection timeout for the request.
     *
     * @param int $ms Timeout in milliseconds
     * @return $this
     */
    public function connectTimeout(int $ms): self
    {
        $this->connectTimeout = $ms;

        return $this;
    }

    /**
     * Configure retry logic for failed requests.
     *
     * @param int $times Number of retry attempts
     * @param int $sleepMs Delay between retries in milliseconds
     * @param callable|null $when Optional callback to determine whether to retry
     * @return $this
     */
    public function retry(int $times, int $sleepMs = 0, ?callable $when = null): self
    {
        $this->maxRetries = $times;
        $this->retryDelayMs = $sleepMs;
        $this->retryWhen = $when instanceof Closure ? $when : ($when ? Closure::fromCallable($when) : null);

        return $this;
    }

    /**
     * Send a request to the FastCGI server.
     *
     * @param string $host The hostname or IP address of the FastCGI server
     * @param int|null $port The port of the FastCGI server (default: 9000)
     * @param string $scriptPath The filesystem path to the PHP script on the server
     * @param RequestMethod $method The HTTP method to use
     * @param bool $isJson Whether to send the request as JSON
     * @param bool $asForm Whether to send the request as form data
     * @return Response
     *
     * @throws Throwable
     */
    private function sendRequest(
        string $host,
        ?int $port,
        string $scriptPath,
        RequestMethod $method,
        bool $isJson = false,
        bool $asForm = false
    ): Response {
        $port ??= 9000;

        $this->connection = new NetworkConnection(
            $host,
            $port,
            $this->connectTimeout,
            $this->readTimeout
        );

        $this->scriptPath = $scriptPath;

        $builder = (new RequestBuilder)
            ->method($method)
            ->path($this->scriptPath)
            ->withHeaders($this->headers);

        if ($method === RequestMethod::GET) {
            $builder->query($this->query);
        } elseif ($isJson) {
            $builder->acceptJson()->json($this->payload);
        } elseif ($asForm) {
            $builder->asForm()->formData($this->payload);
        } elseif (!empty($this->rawBody)) {
            $builder->withBody($this->rawBody, $this->rawBodyType);
        }

        $request = $builder->build();

        if (!empty($this->uri)) {
            $request = $request->withServerParam('REQUEST_URI', $this->uri);
        }

        foreach ($this->serverParams as $key => $value) {
            $request = $request->withServerParam($key, $value);
        }

        foreach ($this->customVars as $key => $value) {
            $request = $request->withCustomVar($key, $value);
        }

        $attempts = 0;

        do {
            try {
                return $this->client->sendRequest($this->connection, $request);
            } catch (Throwable $e) {
                $shouldRetry = $this->retryWhen ? ($this->retryWhen)($e, $request) : true;

                if (++$attempts > $this->maxRetries || !$shouldRetry) {
                    throw $e;
                }

                if ($this->retryDelayMs > 0) {
                    usleep($this->retryDelayMs * 1000);
                }
            }
        } while (true);
    }

    /**
     * Send a GET request to the FastCGI server.
     *
     * @param string $host The hostname or IP address of the FastCGI server
     * @param string $scriptPath The filesystem path to the PHP script on the server
     * @param int|null $port The port of the FastCGI server (default: 9000)
     * @return Response
     *
     * @throws Throwable
     */
    public function get(string $host, string $scriptPath, ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::GET);
    }

    /**
     * Send a POST request to the FastCGI server.
     *
     * @param string $host The hostname or IP address of the FastCGI server
     * @param string $scriptPath The filesystem path to the PHP script on the server
     * @param int|null $port The port of the FastCGI server (default: 9000)
     * @return Response
     *
     * @throws Throwable
     */
    public function post(string $host, string $scriptPath, ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::POST);
    }

    /**
     * Send a PUT request to the FastCGI server.
     *
     * @param string $host The hostname or IP address of the FastCGI server
     * @param string $scriptPath The filesystem path to the PHP script on the server
     * @param int|null $port The port of the FastCGI server (default: 9000)
     * @return Response
     *
     * @throws Throwable
     */
    public function put(string $host, string $scriptPath, ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::PUT);
    }

    /**
     * Send a PATCH request to the FastCGI server.
     *
     * @param string $host The hostname or IP address of the FastCGI server
     * @param string $scriptPath The filesystem path to the PHP script on the server
     * @param int|null $port The port of the FastCGI server (default: 9000)
     * @return Response
     *
     * @throws Throwable
     */
    public function patch(string $host, string $scriptPath, ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::PATCH);
    }

    /**
     * Send a DELETE request to the FastCGI server.
     *
     * @param string $host The hostname or IP address of the FastCGI server
     * @param string $scriptPath The filesystem path to the PHP script on the server
     * @param int|null $port The port of the FastCGI server (default: 9000)
     * @return Response
     *
     * @throws Throwable
     */
    public function delete(string $host, string $scriptPath, ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::DELETE);
    }

    /**
     * Send a POST request with JSON payload to the FastCGI server.
     *
     * @param string $host The hostname or IP address of the FastCGI server
     * @param string $scriptPath The filesystem path to the PHP script on the server
     * @param int|null $port The port of the FastCGI server (default: 9000)
     * @return Response
     *
     * @throws Throwable
     */
    public function json(string $host, string $scriptPath, ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::POST, isJson: true);
    }

    /**
     * Send a POST request with form-encoded data to the FastCGI server.
     *
     * @param string $host The hostname or IP address of the FastCGI server
     * @param string $scriptPath The filesystem path to the PHP script on the server
     * @param int|null $port The port of the FastCGI server (default: 9000)
     * @return Response
     *
     * @throws Throwable
     */
    public function form(string $host, string $scriptPath, ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::POST, asForm: true);
    }

}
