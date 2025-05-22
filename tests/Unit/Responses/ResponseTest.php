<?php

use Rizwan\LaravelFcgiClient\Responses\Response;

test('parses headers correctly', function () {
    $output = "Content-Type: text/html\r\nX-Custom: Value\r\n\r\nBody content";
    $response = new Response($output, '', 0.5);

    $headers = array_change_key_case($response->getHeaders(), CASE_LOWER);

    expect($headers)->toHaveKey('content-type')
        ->and($headers['content-type'])->toBe(['text/html'])
        ->and($headers)->toHaveKey('x-custom')
        ->and($headers['x-custom'])->toBe(['Value']);
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

    expect($cookies)->toBe(['cookie1=value1', 'cookie2=value2'])
        ->and($headerLine)->toBe('cookie1=value1, cookie2=value2');
});

test('exposes output, error and duration', function () {
    $output = "Content-Type: text/html\r\n\r\nBody content";
    $error = 'Some error';
    $duration = 0.5;
    $connectDuration = 0.1;
    $writeDuration = 0.2;

    $response = new Response($output, $error, $duration, $connectDuration, $writeDuration);

    expect($response->getOutput())->toBe($output)
        ->and($response->getError())->toBe($error)
        ->and($response->getDuration())->toBe($duration)
        ->and($response->getConnectDuration())->toBe($connectDuration)
        ->and($response->getWriteDuration())->toBe($writeDuration);
});

test('determines success based on error and status', function () {
    $responseWithError = new Response("Content-Type: text/html\r\n\r\nBody", 'Some error', 0.5);
    expect($responseWithError->successful())->toBeFalse();

    $responseWith500Status = new Response("Status: 500 Internal Server Error\r\n\r\nOops", '', 0.5);
    expect($responseWith500Status->successful())->toBeFalse();

    $successResponse = new Response("Content-Type: text/html\r\n\r\nOK", '', 0.5);
    expect($successResponse->successful())->toBeTrue();
});

test('status defaults to 200 when Status header is missing', function () {
    $output = "Content-Type: text/html\r\n\r\nBody content";
    $response = new Response($output, '', 0.5);

    expect($response->status())->toBe(200)
        ->and($response->ok())->toBeTrue();
});

test('extracts status message from header', function () {
    $output = "Status: 201 Created\r\n\r\n";
    $response = new Response($output, '', 0.5);

    expect($response->statusMessage())->toBe('Created');
});

test('toArray includes durations', function () {
    $response = new Response(
        "Content-Type: text/plain\r\n\r\nBody",
        '',
        0.3,
        100.0,
        200.0
    );

    $array = $response->toArray();


    expect($array['connect_duration_ms'])->toBe(round(100.0, 2))
        ->and($array['status_message'])->toBeNull()
        ->and($array['write_duration_ms'])->toBe(round(200.0, 2))
        ->and($array['attempts'])->toBe(0);
});
