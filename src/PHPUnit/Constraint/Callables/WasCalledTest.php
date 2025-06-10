<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Constraint\Callables;

use Craftzing\TestBench\PHPUnit\DataProviders\QuantableConstraint;
use Craftzing\TestBench\PHPUnit\Doubles\SpyCallable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * @codeCoverageIgnore
 */
final class WasCalledTest extends TestCase
{
    #[Test]
    public function itCanConstructWithoutArguments(): void
    {
        $instance = new WasCalled();

        $this->assertNull($instance->assertInvocation);
        $this->assertNull($instance->times);
    }

    #[Test]
    public function itCanConstructWithInvocationAssertions(): void
    {
        $assertInvocation = function (): void {};

        $instance = new WasCalled($assertInvocation);

        $this->assertSame($assertInvocation, $instance->assertInvocation);
        $this->assertNull($instance->times);
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraint::class, 'cases')]
    public function itImplementsTheQuantableInterface(QuantableConstraint $quantise): void
    {
        $assertInvocation = function (): void {};
        $instance = new WasCalled($assertInvocation);

        $quantisedInstance = $quantise($instance);

        $this->assertNull($instance->times);
        $this->assertSame($assertInvocation, $instance->assertInvocation);
        $this->assertSame($quantise->times, $quantisedInstance->times);
        $this->assertSame($assertInvocation, $quantisedInstance->assertInvocation);
    }

    #[Test]
    public function itFailsWhenEvaluatingForNonSpyCallableInstances(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->assertThat(function (): void {}, new WasCalled());
    }

    #[Test]
    public function itFailsWhenNotCalled(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('was called.');

        $this->assertThat(new SpyCallable(), new WasCalled());
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraint::class, 'atLeastOnce')]
    public function itPassesWhenCalled(QuantableConstraint $quantise): void
    {
        $callable = new SpyCallable();

        $quantise->applyTo($callable);

        $this->assertThat($callable, new WasCalled());
    }

    #[Test]
    public function itFailsWhenNotCalledWithExpectedArguments(): void
    {
        $callable = new SpyCallable();
        $callable('last', 'first');

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('was called with given invocation assertions.');

        $this->assertThat($callable, new WasCalled(function (string $first, string $last): void {
            $this->assertSame('first', $first);
            $this->assertSame('last', $last);
        }));
    }

    #[Test]
    public function itPassesWhenCalledAtLeastOnceWithExpectedArguments(): void
    {
        $callable = new SpyCallable();
        $callable('one', 'two');
        $callable('first', 'last');

        $this->assertThat($callable, new WasCalled(function (string $first, string $last): void {
            $this->assertSame('first', $first);
            $this->assertSame('last', $last);
        }));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraint::class, 'tooFewOrTooManyTimes')]
    public function itFailsWhenNotCalledExpectedTimes(QuantableConstraint $quantise): void
    {
        $callable = new SpyCallable();
        $quantise->applyTo($callable(...));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("was called $quantise->expected time(s).");

        $this->assertThat($callable, new WasCalled()->times($quantise->expected));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraint::class, 'cases')]
    public function itPassesWhenCalledExpectedTimes(QuantableConstraint $quantise): void
    {
        $callable = new SpyCallable();

        $quantise->applyTo($callable(...));

        $this->assertThat($callable, $quantise(new WasCalled()));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraint::class, 'tooFewOrTooManyTimes')]
    public function itFailsWhenNotCalledExpectedTimesWithExpectedArguments(QuantableConstraint $quantise): void
    {
        $callable = new SpyCallable();
        $quantise->applyTo(fn () => $callable('first', 'last'));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("was called $quantise->expected time(s) with given invocation assertions.");

        $this->assertThat($callable, new WasCalled(function (string $first, string $last): void {
            $this->assertSame('first', $first);
            $this->assertSame('last', $last);
        })->times($quantise->expected));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraint::class, 'cases')]
    public function itPassesWhenCalledExpectedTimesWithExpectedArguments(QuantableConstraint $quantise): void
    {
        $callable = new SpyCallable();

        $quantise->applyTo(fn () => $callable('first', 'last'));

        $this->assertThat($callable, $quantise(new WasCalled(function (string $first, string $last): void {
            $this->assertSame('first', $first);
            $this->assertSame('last', $last);
        })));
    }
}
