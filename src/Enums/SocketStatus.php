<?php
namespace Rizwan\LaravelFcgiClient\Enums;

enum SocketStatus: int
{
    case INIT = 1;
    case BUSY = 2;
    case IDLE = 3;

    /**
     * @return bool
     */
    public function isAvailable(): bool
    {
        return match ($this) {
            self::INIT, self::IDLE => true,
            self::BUSY => false,
        };
    }
}
