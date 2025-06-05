<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Doubles\Enums;

enum StringBackedEnum: string
{
    case One = 'One';
    case Two = 'Two';
    case Three = 'Three';
}
