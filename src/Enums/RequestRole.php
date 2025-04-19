<?php

namespace Rizwan\LaravelFcgiClient\Enums;

enum RequestRole: int
{
    case RESPONDER = 1;
    case AUTHORIZER = 2;
    case FILTER = 3;
}
