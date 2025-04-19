<?php

namespace Rizwan\LaravelFcgiClient\Client;

use InvalidArgumentException;

class SocketId
{
    private function __construct(
        private readonly int $id
    ) {
        $this->guardValidId($id);
    }

    private function guardValidId(int $id): void
    {
        if ($id < 1 || $id > ((1 << 16) - 1)) {
            throw new InvalidArgumentException("Invalid socket ID (out of range): $id");
        }
    }

    public static function generate(): self
    {
        return new self(random_int(1, (1 << 16) - 1));
    }

    public function getValue(): int
    {
        return $this->id;
    }

    public function equals(SocketId $other): bool
    {
        return $this->id === $other->id;
    }
}
