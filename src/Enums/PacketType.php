<?php

namespace Rizwan\LaravelFcgiClient\Enums;

enum PacketType: int
{
    case BEGIN_REQUEST = 1;
    case ABORT_REQUEST = 2;
    case END_REQUEST = 3;
    case PARAMS = 4;
    case STDIN = 5;
    case STDOUT = 6;
    case STDERR = 7;
    case DATA = 8;
    case GET_VALUES = 9;
    case GET_VALUES_RESULT = 10;
    case UNKNOWN_TYPE = 11;
}
