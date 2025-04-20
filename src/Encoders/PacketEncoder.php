<?php

namespace Rizwan\LaravelFcgiClient\Encoders;

use InvalidArgumentException;
use Rizwan\LaravelFcgiClient\Enums\PacketType;

final class PacketEncoder
{
    private const int VERSION = 1;

    /**
     * Encode a FastCGI packet with the given type, content, and request ID.
     *
     * @param PacketType $type
     * @param string $content
     * @param int $requestId
     * @return string
     */
    public function encodePacket(PacketType $type, string $content, int $requestId): string
    {
        $contentLength = strlen($content);

        return chr(self::VERSION)                         // Version (1)
            . chr($type->value)                                    // Type
            . chr(($requestId >> 8) & 0xFF)               // Request ID High Byte
            . chr($requestId & 0xFF)                      // Request ID Low Byte
            . chr(($contentLength >> 8) & 0xFF)           // Content Length High Byte
            . chr($contentLength & 0xFF)                  // Content Length Low Byte
            . chr(0)                                      // Padding Length
            . chr(0)                                      // Reserved
            . $content;
    }

    /**
     * Decode an 8-byte FastCGI packet header into an associative array.
     *
     * @param string $data
     * @return array{
     *     version: int,
     *     type: int,
     *     requestId: int,
     *     contentLength: int,
     *     paddingLength: int,
     *     reserved: int
     * }
     */
    public function decodeHeader(string $data): array
    {
        if (strlen($data) !== 8) {
            throw new InvalidArgumentException('Header must be exactly 8 bytes.');
        }

        return [
            'version'       => ord($data[0]),
            'type'          => ord($data[1]),
            'requestId'     => (ord($data[2]) << 8) | ord($data[3]),
            'contentLength' => (ord($data[4]) << 8) | ord($data[5]),
            'paddingLength' => ord($data[6]),
            'reserved'      => ord($data[7]),
        ];
    }
}
