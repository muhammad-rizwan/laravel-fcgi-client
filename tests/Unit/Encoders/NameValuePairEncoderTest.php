<?php

use Rizwan\LaravelFcgiClient\Encoders\NameValuePairEncoder;

beforeEach(function () {
    $this->encoder = new NameValuePairEncoder;
});

test('encodes pair with short values', function () {
    $name = 'SCRIPT_FILENAME';
    $value = '/path/to/script.php';

    $result = $this->encoder->encodePair($name, $value);

    // Check length bytes are correct
    expect(substr($result, 0, 1))->toBe(chr(strlen($name)));
    expect(substr($result, 1, 1))->toBe(chr(strlen($value)));

    // Check that name and value are included
    expect(substr($result, 2))->toBe($name.$value);
});

test('encodes multiple pairs', function () {
    $pairs = [
        'SCRIPT_FILENAME' => '/path/to/script.php',
        'REQUEST_METHOD' => 'GET',
        'QUERY_STRING' => 'param=value',
    ];

    $result = $this->encoder->encodePairs($pairs);

    // Verify each pair is encoded correctly
    foreach ($pairs as $name => $value) {
        $encoded = $this->encoder->encodePair($name, $value);
        expect($result)->toContain($encoded);
    }
});

test('decoded pairs match original data', function () {
    $pairs = [
        'SCRIPT_FILENAME' => '/path/to/script.php',
        'REQUEST_METHOD' => 'GET',
        'QUERY_STRING' => 'param=value',
    ];

    $encoded = $this->encoder->encodePairs($pairs);
    $decoded = $this->encoder->decodePairs($encoded);

    expect($decoded)->toBe($pairs);
});
