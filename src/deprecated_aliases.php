<?php

declare(strict_types=1);

use Craftzing\TestBench\Doubles\Callable\CallableInvocation;
use Craftzing\TestBench\Doubles\Callable\SpyCallable;
use Craftzing\TestBench\Doubles\Enum\IntBackedEnum;
use Craftzing\TestBench\Doubles\Enum\StringBackedEnum;
use Craftzing\TestBench\Doubles\Enum\UnitEnum;

/**
 * @deprecated since v1.2
 * TODO v2: Remove these aliases in favour of the new FQCNs.
 */
class_alias(CallableInvocation::class, 'Craftzing\TestBench\PHPUnit\Doubles\CallableInvocation');
class_alias(SpyCallable::class, 'Craftzing\TestBench\PHPUnit\Doubles\SpyCallable');
class_alias(StringBackedEnum::class, 'Craftzing\TestBench\PHPUnit\Doubles\Enums\StringBackedEnum');
class_alias(IntBackedEnum::class, 'Craftzing\TestBench\PHPUnit\Doubles\Enums\IntBackedEnum');
class_alias(UnitEnum::class, 'Craftzing\TestBench\PHPUnit\Doubles\Enums\UnitEnum');
