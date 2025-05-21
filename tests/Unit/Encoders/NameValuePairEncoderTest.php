<?php

use Rizwan\LaravelFcgiClient\Encoders\NameValuePairEncoder;

beforeEach(function () {
    $this->encoder = new NameValuePairEncoder;
});

test('encodes single name-value pair correctly', function () {
    $name = 'SCRIPT_FILENAME';
    $value = '/path/to/script.php';

    $encoded = $this->encoder->encodePair($name, $value);

    // Length bytes + name + value
    $expectedLength = 1 + 1 + strlen($name) + strlen($value);

    expect(strlen($encoded))->toBe($expectedLength);
    expect(substr($encoded, 2))->toBe($name.$value);
});

test('encodes multiple name-value pairs', function () {
    $pairs = [
        'SCRIPT_FILENAME' => '/index.php',
        'REQUEST_METHOD' => 'GET',
    ];

    $encoded = $this->encoder->encodePairs($pairs);

    foreach ($pairs as $key => $value) {
        $pairEncoded = $this->encoder->encodePair($key, $value);
        expect($encoded)->toContain($pairEncoded);
    }
});

test('decodes encoded name-value pairs', function () {
    $pairs = [
        'SCRIPT_FILENAME' => '/index.php',
        'REQUEST_METHOD' => 'POST',
        'QUERY_STRING' => 'id=42',
    ];

    $encoded = $this->encoder->encodePairs($pairs);
    $decoded = $this->encoder->decodePairs($encoded);

    expect($decoded)->toBe($pairs);
});
