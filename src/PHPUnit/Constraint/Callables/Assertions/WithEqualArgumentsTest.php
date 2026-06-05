<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Constraint\Callables\Assertions;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use stdClass;

final class WithEqualArgumentsTest extends TestCase
{
    #[Test]
    #[TestWith(['first'], 'Too few arguments')]
    #[TestWith(['first', 'second', 'last'], 'Too many arguments')]
    public function itFailsWhenInvokedWithDifferentAmountOfArguments(string ...$actual): void
    {
        $instance = new WithEqualArguments('first', 'last');

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('was invoked with a different amount of arguments');

        $instance->__invoke(...$actual);
    }

    #[Test]
    #[TestWith([1, 'first different', 'last'])]
    #[TestWith([2, 'first', 'last different'])]
    public function itFailsWhenInvokedWithDifferentArguments(int $argument, string ...$actual): void
    {
        $instance = new WithEqualArguments('first', 'last');

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("Argument #{$argument} passed to callable does not match expected value");

        $instance->__invoke(...$actual);
    }

    #[Test]
    public function itFailsWhenInvokedWithArgumentsInDifferentOrder(): void
    {
        $instance = new WithEqualArguments('first', 'last');

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Argument #1 passed to callable does not match expected value');

        $instance->__invoke('last', 'first');
    }

    #[Test]
    #[TestWith(['first', 'second', 'last'], 'Scalar arguments')]
    #[TestWith([new stdClass()], 'Object arguments')]
    public function itPassesWhenInvokedWithSameArguments(mixed ...$actual): void
    {
        $instance = new WithEqualArguments(...$actual);

        $instance->__invoke(...$actual);
    }

    #[Test]
    public function itPassesWhenInvokedWithEqualArguments(): void
    {
        $instance = new WithEqualArguments(new stdClass());

        $instance->__invoke(new stdClass());
    }
}
