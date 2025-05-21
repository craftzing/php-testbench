<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Constraint\Callables;

use Craftzing\TestBench\PHPUnit\Doubles\SpyCallable;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

use function random_int;

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
    public function itCanConstructWithExpectedInvocationOfTimes(): void
    {
        $assertInvocation = function (): void {};
        $times = random_int(1, 100);
        $instance = new WasCalled($assertInvocation);

        $instanceWithTimes = $instance->times($times);

        $this->assertNull($instance->times);
        $this->assertSame($assertInvocation, $instance->assertInvocation);
        $this->assertSame($times, $instanceWithTimes->times);
        $this->assertSame($assertInvocation, $instanceWithTimes->assertInvocation);
    }

    #[Test]
    public function itCanConstructWithExpectationsNeverToBeCalled(): void
    {
        $instance = new WasCalled();

        $instanceWithTimes = $instance->never();

        $this->assertNull($instance->times);
        $this->assertNull($instance->assertInvocation);
        $this->assertSame(0, $instanceWithTimes->times);
        $this->assertNull($instanceWithTimes->assertInvocation);
    }

    #[Test]
    public function itCanConstructWithExpectationsToBeCalledOnce(): void
    {
        $instance = new WasCalled();

        $instanceWithTimes = $instance->once();

        $this->assertNull($instance->times);
        $this->assertNull($instance->assertInvocation);
        $this->assertSame(1, $instanceWithTimes->times);
        $this->assertNull($instanceWithTimes->assertInvocation);
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
    #[TestWith([1], 'Once')]
    #[TestWith([3], 'Multiple times')]
    public function itPassesWhenCalled(int $times): void
    {
        $callable = new SpyCallable();

        Collection::times($times, $callable(...));

        $this->assertThat($callable, new WasCalled());
    }

    #[Test]
    public function itFailsWhenNotCalledWithExpectedArguments(): void
    {
        $callable = new SpyCallable();
        $callable('last', 'first');

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('was called with the expected invocation assertions.');

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
    #[TestWith([2, 1], 'Too few times')]
    #[TestWith([2, 3], 'Too many times')]
    public function itFailsWhenNotCalledExpectedTimes(int $expected, int $actual): void
    {
        $callable = new SpyCallable();
        Collection::times($actual, $callable(...));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("was called $expected times.");

        $this->assertThat($callable, new WasCalled()->times($expected));
    }

    #[Test]
    public function itPassesWhenCalledExpectedTimes(): void
    {
        $expected = random_int(1, 10);
        $callable = new SpyCallable();

        Collection::times($expected, $callable(...));

        $this->assertThat($callable, new WasCalled()->times($expected));
    }

    #[Test]
    #[TestWith([2, 1], 'Too few times')]
    #[TestWith([2, 3], 'Too many times')]
    public function itFailsWhenNotCalledExpectedTimesWithExpectedArguments(int $expected, int $actual): void
    {
        $callable = new SpyCallable();
        Collection::times($actual, fn () => $callable('first', 'last'));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("was called $expected times with the expected invocation assertions.");

        $this->assertThat($callable, new WasCalled(function (string $first, string $last): void {
            $this->assertSame('first', $first);
            $this->assertSame('last', $last);
        })->times($expected));
    }

    #[Test]
    public function itPassesWhenCalledExpectedTimesWithExpectedArguments(): void
    {
        $expected = random_int(1, 10);
        $callable = new SpyCallable();

        Collection::times($expected, fn () => $callable('first', 'last'));

        $this->assertThat($callable, new WasCalled(function (string $first, string $last): void {
            $this->assertSame('first', $first);
            $this->assertSame('last', $last);
        })->times($expected));
    }
}
