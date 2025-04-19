<?php

namespace Rizwan\LaravelFcgiClient\Encoders;

use Rizwan\LaravelFcgiClient\Enums\PacketType;

class PacketEncoder
{
    private const VERSION = 1;

    public function encodePacket(PacketType $type, string $content, int $requestId): string
    {
        $contentLength = strlen($content);

        return chr(self::VERSION)                     /* version */
            . chr($type->value)                    /* type */
            . chr(($requestId >> 8) & 0xFF)        /* requestIdB1 */
            . chr($requestId & 0xFF)               /* requestIdB0 */
            . chr(($contentLength >> 8) & 0xFF)    /* contentLengthB1 */
            . chr($contentLength & 0xFF)           /* contentLengthB0 */
            . chr(0)                               /* paddingLength */
            . chr(0)                               /* reserved */
            . $content;                            /* content */
    }

    public function decodeHeader(string $data): array
    {
        return [
            'version'       => ord($data[0]),
            'type'          => ord($data[1]),
            'requestId'     => (ord($data[2]) << 8) + ord($data[3]),
            'contentLength' => (ord($data[4]) << 8) + ord($data[5]),
            'paddingLength' => ord($data[6]),
            'reserved'      => ord($data[7]),
        ];
    }
}
