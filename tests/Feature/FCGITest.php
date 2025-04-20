<?php

use Rizwan\LaravelFcgiClient\Facades\FCGI;
use Rizwan\LaravelFcgiClient\Responses\Response;

// Mock the connection and response for testing
beforeEach(function () {
    $this->app->bind(\Rizwan\LaravelFcgiClient\Client\Client::class, function () {
        $mockClient = Mockery::mock(\Rizwan\LaravelFcgiClient\Client\Client::class);
        $mockClient->shouldReceive('sendRequest')
            ->andReturn(new Response(
                "Content-Type: text/html\r\n\r\nTest response body",
                '',
                0.1
            ));

        return $mockClient;
    });
});

test('can make GET request through facade', function () {
    $response = FCGI::baseUrl('tcp://example.com:9000')
        ->get('/test.php');

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getBody())->toBe('Test response body');
    expect($response->successful())->toBeTrue();
});

test('can define and use named connections', function () {
    FCGI::defineConnection('testing', 'tcp://example.com:9000');

    $response = FCGI::connection('testing')
        ->get('/test.php');

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getBody())->toBe('Test response body');
});

test('can make POST request with data', function () {
    $response = FCGI::baseUrl('tcp://example.com:9000')
        ->post('/test.php', ['key' => 'value']);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getBody())->toBe('Test response body');
});

test('can make JSON request', function () {
    $response = FCGI::baseUrl('tcp://example.com:9000')
        ->json('/test.php', ['key' => 'value']);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getBody())->toBe('Test response body');
});

// Note: Testing parallel requests would require mocking the pool behavior
// which is more complex and would require a custom mock implementation
