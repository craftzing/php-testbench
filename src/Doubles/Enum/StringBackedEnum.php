<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Doubles\Enum;

enum StringBackedEnum: string
{
    case One = 'One';
    case Two = 'Two';
    case Three = 'Three';
    case Four = 'Four';
}
