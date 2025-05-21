<?php

use Rizwan\LaravelFcgiClient\Responses\Response;

test('parses headers correctly', function () {
    $output = "Content-Type: text/html\r\nX-Custom: Value\r\n\r\nBody content";
    $response = new Response($output, '', 0.5);

    $headers = array_change_key_case($response->getHeaders(), CASE_LOWER);

    expect($headers)->toHaveKey('content-type');
    expect($headers['content-type'])->toBe(['text/html']);
    expect($headers)->toHaveKey('x-custom');
    expect($headers['x-custom'])->toBe(['Value']);
});

test('extracts body correctly', function () {
    $output = "Content-Type: text/html\r\n\r\nBody content";
    $response = new Response($output, '', 0.5);

    expect($response->getBody())->toBe('Body content');
});

test('handles multiple headers with same name', function () {
    $output = "Set-Cookie: cookie1=value1\r\nSet-Cookie: cookie2=value2\r\n\r\nBody";
    $response = new Response($output, '', 0.5);

    $cookies = $response->getHeader('Set-Cookie');
    $headerLine = $response->getHeaderLine('Set-Cookie');

    expect($cookies)->toBe(['cookie1=value1', 'cookie2=value2']);
    expect($headerLine)->toBe('cookie1=value1, cookie2=value2');
});

test('exposes output, error and duration', function () {
    $output = "Content-Type: text/html\r\n\r\nBody content";
    $error = 'Some error';
    $duration = 0.5;

    $response = new Response($output, $error, $duration);

    expect($response->getOutput())->toBe($output);
    expect($response->getError())->toBe($error);
    expect($response->getDuration())->toBe($duration);
});

test('determines success based on error and status', function () {
    $responseWithError = new Response("Content-Type: text/html\r\n\r\nBody", 'Some error', 0.5);
    expect($responseWithError->successful())->toBeFalse();

    $responseWith500Status = new Response("Status: 500 Internal Server Error\r\n\r\nOops", '', 0.5);
    expect($responseWith500Status->successful())->toBeFalse();

    $successResponse = new Response("Content-Type: text/html\r\n\r\nOK", '', 0.5);
    expect($successResponse->successful())->toBeTrue();
});
