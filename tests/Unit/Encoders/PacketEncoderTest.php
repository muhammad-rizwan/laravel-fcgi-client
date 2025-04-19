<?php

use Rizwan\LaravelFcgiClient\Encoders\PacketEncoder;
use Rizwan\LaravelFcgiClient\Enums\PacketType;

beforeEach(function () {
    $this->encoder = new PacketEncoder();
});

test('encodes packet with correct structure', function () {
    $type = PacketType::BEGIN_REQUEST;
    $content = 'test content';
    $requestId = 1;

    $result = $this->encoder->encodePacket($type, $content, $requestId);

    // Verify packet structure
    expect(ord($result[0]))->toBe(1); // Version
    expect(ord($result[1]))->toBe($type->value); // Type
    expect(ord($result[2]) << 8 | ord($result[3]))->toBe($requestId); // Request ID
    expect(ord($result[4]) << 8 | ord($result[5]))->toBe(strlen($content)); // Content Length
    expect(ord($result[6]))->toBe(0); // Padding Length
    expect(ord($result[7]))->toBe(0); // Reserved
    expect(substr($result, 8))->toBe($content); // Content
});

test('decodes header correctly', function () {
    $type = PacketType::BEGIN_REQUEST;
    $content = 'test content';
    $requestId = 1;

    $packetData = $this->encoder->encodePacket($type, $content, $requestId);
    $header = substr($packetData, 0, 8);

    $decoded = $this->encoder->decodeHeader($header);

    expect($decoded['version'])->toBe(1);
    expect($decoded['type'])->toBe($type->value);
    expect($decoded['requestId'])->toBe($requestId);
    expect($decoded['contentLength'])->toBe(strlen($content));
    expect($decoded['paddingLength'])->toBe(0);
    expect($decoded['reserved'])->toBe(0);
});
