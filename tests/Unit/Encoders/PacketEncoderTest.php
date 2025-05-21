<?php

use Rizwan\LaravelFcgiClient\Encoders\PacketEncoder;
use Rizwan\LaravelFcgiClient\Enums\PacketType;

beforeEach(function () {
    $this->encoder = new PacketEncoder;
});

test('encodes packet with correct structure', function () {
    $type = PacketType::BEGIN_REQUEST;
    $content = 'test content';
    $requestId = 1;

    $packet = $this->encoder->encodePacket($type, $content, $requestId);

    expect(ord($packet[0]))->toBe(1); // FCGI_VERSION_1
    expect(ord($packet[1]))->toBe($type->value);
    expect((ord($packet[2]) << 8) | ord($packet[3]))->toBe($requestId);
    expect((ord($packet[4]) << 8) | ord($packet[5]))->toBe(strlen($content));
    expect(ord($packet[6]))->toBe(0); // paddingLength
    expect(ord($packet[7]))->toBe(0); // reserved
    expect(substr($packet, 8))->toBe($content);
});

test('decodes header correctly', function () {
    $type = PacketType::STDIN;
    $content = 'body';
    $requestId = 10;

    $packet = $this->encoder->encodePacket($type, $content, $requestId);
    $header = substr($packet, 0, 8);

    $decoded = $this->encoder->decodeHeader($header);

    expect($decoded)->toMatchArray([
        'version' => 1,
        'type' => $type->value,
        'requestId' => $requestId,
        'contentLength' => strlen($content),
        'paddingLength' => 0,
        'reserved' => 0,
    ]);
});
