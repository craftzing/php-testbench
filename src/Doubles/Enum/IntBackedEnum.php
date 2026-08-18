<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Doubles\Enum;

enum IntBackedEnum: int
{
    case One = 1;
    case Two = 2;
    case Three = 3;
    case Four = 4;
}
