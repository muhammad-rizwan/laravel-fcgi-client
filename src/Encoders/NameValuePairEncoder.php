<?php

namespace Rizwan\LaravelFcgiClient\Encoders;

class NameValuePairEncoder
{
    public function encodePairs(array $pairs): string
    {
        $encoded = '';

        foreach ($pairs as $key => $value) {
            $encoded .= $this->encodePair((string) $key, (string) $value);
        }

        return $encoded;
    }

    public function encodePair(string $name, string $value): string
    {
        $nameLength = strlen($name);
        $valueLength = strlen($value);

        if ($nameLength < 128) {
            $nameValuePair = chr($nameLength);
        } else {
            $nameValuePair = chr(($nameLength >> 24) | 0x80)
                .chr(($nameLength >> 16) & 0xFF)
                .chr(($nameLength >> 8) & 0xFF)
                .chr($nameLength & 0xFF);
        }

        if ($valueLength < 128) {
            $nameValuePair .= chr($valueLength);
        } else {
            $nameValuePair .= chr(($valueLength >> 24) | 0x80)
                .chr(($valueLength >> 16) & 0xFF)
                .chr(($valueLength >> 8) & 0xFF)
                .chr($valueLength & 0xFF);
        }

        return $nameValuePair.$name.$value;
    }

    public function decodePairs(string $data, int $length = -1): array
    {
        $array = [];

        if ($length === -1) {
            $length = strlen($data);
        }

        $p = 0;

        while ($p !== $length) {
            $nameLength = ord($data[$p++]);
            if ($nameLength >= 128) {
                $nameLength = ($nameLength & 0x7F) << 24;
                $nameLength |= (ord($data[$p++]) << 16);
                $nameLength |= (ord($data[$p++]) << 8);
                $nameLength |= ord($data[$p++]);
            }

            $valueLength = ord($data[$p++]);
            if ($valueLength >= 128) {
                $valueLength = ($valueLength & 0x7F) << 24;
                $valueLength |= (ord($data[$p++]) << 16);
                $valueLength |= (ord($data[$p++]) << 8);
                $valueLength |= ord($data[$p++]);
            }

            $array[substr($data, $p, $nameLength)] = substr($data, $p + $nameLength, $valueLength);
            $p += ($nameLength + $valueLength);
        }

        return $array;
    }
}
