<?php

namespace Rizwan\LaravelFcgiClient\Enums;

enum ProtocolStatus: int
{
    case REQUEST_COMPLETE = 0;
    case CANT_MPX_CONN = 1;
    case OVERLOADED = 2;
    case UNKNOWN_ROLE = 3;
}
