<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Constraint\Callables\Assertions;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use stdClass;

final class WithSameArgumentsTest extends TestCase
{
    #[Test]
    #[TestWith(['first'], 'Too few arguments')]
    #[TestWith(['first', 'second', 'last'], 'Too many arguments')]
    public function itFailsWhenInvokedWithDifferentAmountOfArguments(string ...$actual): void
    {
        $instance = new WithSameArguments('first', 'last');

        $this->expectException(ExpectationFailedException::class);

        $instance->__invoke(...$actual);
    }

    #[Test]
    #[TestWith(['first different', 'last'])]
    #[TestWith(['first', 'last different'])]
    public function itFailsWhenInvokedWithDifferentArguments(string ...$actual): void
    {
        $instance = new WithSameArguments('first', 'last');

        $this->expectException(ExpectationFailedException::class);

        $instance->__invoke(...$actual);
    }

    #[Test]
    public function itFailsWhenInvokedWithArgumentsInDifferentOrder(): void
    {
        $instance = new WithSameArguments('first', 'last');

        $this->expectException(ExpectationFailedException::class);

        $instance->__invoke('last', 'first');
    }

    #[Test]
    public function itFailsWhenInvokedWithNonIdenticalArguments(): void
    {
        $instance = new WithSameArguments(new stdClass());

        $this->expectException(ExpectationFailedException::class);

        $instance->__invoke(new stdClass());
    }

    #[Test]
    #[TestWith(['first', 'second', 'last'], 'Scalar arguments')]
    #[TestWith([new stdClass()], 'Object arguments')]
    public function itPassesWhenInvokedWithSameArguments(mixed ...$actual): void
    {
        $instance = new WithSameArguments(...$actual);

        $instance->__invoke(...$actual);
    }
}
