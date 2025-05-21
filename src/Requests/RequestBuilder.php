<?php

namespace Rizwan\LaravelFcgiClient\Requests;

use Rizwan\LaravelFcgiClient\Enums\RequestMethod;
use Rizwan\LaravelFcgiClient\RequestContents\ContentInterface;
use Rizwan\LaravelFcgiClient\RequestContents\JsonContent;
use Rizwan\LaravelFcgiClient\RequestContents\RawContent;
use Rizwan\LaravelFcgiClient\RequestContents\UrlEncodedContent;
use Rizwan\LaravelFcgiClient\Support\HeaderBag;

/**
 * Builder for creating FastCGI request objects.
 *
 * This class implements a fluent interface for constructing FastCGI requests
 * with various parameters, headers, and content types.
 */
class RequestBuilder
{
    /**
     * The HTTP method for the request.
     */
    private RequestMethod $method = RequestMethod::GET;

    /**
     * The filesystem path to the PHP script on the FastCGI server.
     */
    private string $scriptPath = '';

    /**
     * The content (body) of the request.
     */
    private ?ContentInterface $content = null;

    /**
     * Server parameters to be included in the request.
     */
    private array $serverParams = [];

    /**
     * Custom FastCGI variables for the request.
     */
    private array $customVars = [];

    /**
     * Container for HTTP headers.
     */
    private HeaderBag $headers;

    /**
     * Initialize a new request builder.
     */
    public function __construct()
    {
        $this->headers = new HeaderBag;
    }

    /**
     * Set the HTTP method for the request.
     *
     * @param RequestMethod $method The HTTP method enum value
     * @return $this
     */
    public function method(RequestMethod $method): self
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Set the script path for the request.
     *
     * This is the filesystem path to the PHP script on the server.
     *
     * @param string $path The script path
     * @return $this
     */
    public function path(string $path): self
    {
        $this->scriptPath = $path;

        return $this;
    }

    /**
     * Set the content object for the request body.
     *
     * @param ContentInterface|null $content The content object
     * @return $this
     */
    public function content(?ContentInterface $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Set query parameters for a GET request.
     *
     * @param array $params Associative array of query parameters
     * @return $this
     */
    public function query(array $params): self
    {
        $this->serverParams['QUERY_STRING'] = http_build_query($params);

        return $this;
    }

    /**
     * Set form-encoded data as the request content.
     *
     * @param array $data Associative array of form data
     * @return $this
     */
    public function formData(array $data): self
    {
        $this->content = new UrlEncodedContent($data);

        return $this;
    }

    /**
     * Set JSON data as the request content.
     *
     * @param array $data Associative array to be encoded as JSON
     * @return $this
     */
    public function json(array $data): self
    {
        $this->content = new JsonContent($data);

        return $this;
    }

    /**
     * Add a single header to the request.
     *
     * @param string $key The header name
     * @param string|int|float $value The header value
     * @return $this
     */
    public function withHeader(string $key, string|int|float $value): self
    {
        $this->headers->set($key, (string) $value);

        return $this;
    }

    /**
     * Add multiple headers to the request.
     *
     * @param array $headers Associative array of headers
     * @return $this
     */
    public function withHeaders(array $headers): self
    {
        foreach ($headers as $key => $value) {
            $this->withHeader($key, $value);
        }

        return $this;
    }

    /**
     * Add a server parameter to the request.
     *
     * These will be available in the $_SERVER superglobal in the PHP script.
     *
     * @param string $key The parameter name
     * @param string $value The parameter value
     * @return $this
     */
    public function withServerParam(string $key, string $value): self
    {
        $this->serverParams[$key] = $value;

        return $this;
    }

    /**
     * Add a custom FastCGI variable to the request.
     *
     * @param string $key The variable name
     * @param mixed $value The variable value
     * @return $this
     */
    public function withCustomVar(string $key, mixed $value): self
    {
        $this->customVars[$key] = $value;

        return $this;
    }

    /**
     * Set the Accept header to a specific MIME type.
     *
     * @param string $type The MIME type to accept
     * @return $this
     */
    public function accept(string $type): self
    {
        return $this->withHeader('Accept', $type);
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
        $this->content = new RawContent($body, $type);

        return $this;
    }

    /**
     * Set the Accept header to 'application/json'.
     *
     * @return $this
     */
    public function acceptJson(): self
    {
        return $this->withHeader('Accept', 'application/json');
    }

    /**
     * Set the Content-Type header to 'application/x-www-form-urlencoded'.
     *
     * @return $this
     */
    public function asForm(): self
    {
        return $this->withHeader('Content-Type', 'application/x-www-form-urlencoded');
    }

    /**
     * Set a custom read timeout for the request.
     *
     * @param int $milliseconds Timeout in milliseconds
     * @return $this
     */
    public function timeout(int $milliseconds): self
    {
        return $this->withCustomVar('FCGI_READ_TIMEOUT', (string) $milliseconds);
    }

    /**
     * Build and return the configured Request object.
     *
     * @return Request
     */
    public function build(): Request
    {
        $request = new Request(
            $this->method,
            $this->scriptPath,
            $this->content
        );

        // Inject headers as server params
        foreach ($this->headers->toServerParams() as $key => $value) {
            $request = $request->withServerParam($key, $value);
        }

        foreach ($this->serverParams as $key => $value) {
            $request = $request->withServerParam($key, $value);
        }

        foreach ($this->customVars as $key => $value) {
            $request = $request->withCustomVar($key, $value);
        }

        return $request;
    }
}
