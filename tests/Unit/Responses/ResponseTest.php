<?php

use Rizwan\LaravelFcgiClient\Responses\Response;

test('parses headers correctly', function () {
    $output = "Content-Type: text/html\r\nX-Custom: Value\r\n\r\nBody content";
    $error = '';
    $duration = 0.5;

    $response = new Response($output, $error, $duration);

    expect($response->getHeaders())->toHaveCount(2);
    expect($response->getHeader('content-type'))->toBe(['text/html']);
    expect($response->getHeader('x-custom'))->toBe(['Value']);
});

test('extracts body correctly', function () {
    $output = "Content-Type: text/html\r\n\r\nBody content";
    $error = '';
    $duration = 0.5;

    $response = new Response($output, $error, $duration);

    expect($response->getBody())->toBe('Body content');
});

test('handles multiple headers with same name', function () {
    $output = "Set-Cookie: cookie1=value1\r\nSet-Cookie: cookie2=value2\r\n\r\nBody";
    $error = '';
    $duration = 0.5;

    $response = new Response($output, $error, $duration);

    expect($response->getHeader('set-cookie'))->toBe(['cookie1=value1', 'cookie2=value2']);
    expect($response->getHeaderLine('set-cookie'))->toBe('cookie1=value1, cookie2=value2');
});

test('provides access to original output and error', function () {
    $output = "Content-Type: text/html\r\n\r\nBody content";
    $error = 'Some error';
    $duration = 0.5;

    $response = new Response($output, $error, $duration);

    expect($response->getOutput())->toBe($output);
    expect($response->getError())->toBe($error);
    expect($response->getDuration())->toBe($duration);
});

test('considers response successful when no error', function () {
    $output = "Content-Type: text/html\r\n\r\nBody content";
    $error = '';

    $response = new Response($output, $error, 0.5);

    expect($response->successful())->toBeTrue();
});

test('considers response unsuccessful when there is an error', function () {
    $output = "Content-Type: text/html\r\n\r\nBody content";
    $error = 'Some error occurred';

    $response = new Response($output, $error, 0.5);

    expect($response->successful())->toBeFalse();
});

test('considers response unsuccessful with error status code', function () {
    $output = "Status: 500 Internal Server Error\r\nContent-Type: text/html\r\n\r\nError page";
    $error = '';

    $response = new Response($output, $error, 0.5);

    expect($response->successful())->toBeFalse();
});
