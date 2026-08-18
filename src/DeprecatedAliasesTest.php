<?php

declare(strict_types=1);

namespace Craftzing\TestBench;

use Craftzing\TestBench\Doubles\Callable\CallableInvocation;
use Craftzing\TestBench\Doubles\Callable\SpyCallable;
use Craftzing\TestBench\Doubles\Enum\IntBackedEnum;
use Craftzing\TestBench\Doubles\Enum\StringBackedEnum;
use Craftzing\TestBench\Doubles\Enum\UnitEnum;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class DeprecatedAliasesTest extends TestCase
{
    #[Test]
    #[TestWith([CallableInvocation::class, 'Craftzing\TestBench\PHPUnit\Doubles\CallableInvocation'])]
    #[TestWith([SpyCallable::class, 'Craftzing\TestBench\PHPUnit\Doubles\SpyCallable'])]
    #[TestWith([StringBackedEnum::class, 'Craftzing\TestBench\PHPUnit\Doubles\Enums\StringBackedEnum'])]
    #[TestWith([IntBackedEnum::class, 'Craftzing\TestBench\PHPUnit\Doubles\Enums\IntBackedEnum'])]
    #[TestWith([UnitEnum::class, 'Craftzing\TestBench\PHPUnit\Doubles\Enums\UnitEnum'])]
    public function itProvidesBackwardsCompatibilityThroughDeprecatedClassAliases(string $expected, string $alias): void
    {
        $actual = new ReflectionClass($alias)->getName();

        $this->assertSame($expected, $actual);
    }
}
