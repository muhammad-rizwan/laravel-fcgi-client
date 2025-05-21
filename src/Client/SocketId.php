<?php

namespace Rizwan\LaravelFcgiClient\Client;

use InvalidArgumentException;

final class SocketId
{
    private function __construct(
        private readonly int $id
    ) {
        $this->guardValidId($id);
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

    /**
     * Validates that the socket ID is within the allowed FastCGI request ID range.
     *
     * According to the FastCGI specification, request IDs must be 16-bit unsigned integers
     * between 1 and 65535 (0 is reserved). This method ensures that any generated or provided
     * socket ID falls within that range.
     *
     * @throws InvalidArgumentException if the ID is out of bounds
     */
    private function guardValidId(int $id): void
    {
        if ($id < 1 || $id > ((1 << 16) - 1)) {
            throw new InvalidArgumentException("Invalid socket ID (out of range): $id");
        }
    }
}
