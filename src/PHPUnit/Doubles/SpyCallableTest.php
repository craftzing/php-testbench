<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Doubles;

use Generator;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

use function call_user_func;
use function random_int;

final class SpyCallableTest extends TestCase
{
    #[Test]
    public function itCanBeInitialisedWithoutProperties(): void
    {
        $instance = new SpyCallable();

        $this->assertNull($instance->returnValueWhenCalled);
        $this->assertNull($instance->exceptionWhenCalled);
    }

    public static function returnValues(): Generator
    {
        yield 'Nullable' => [null];
        yield 'String' => ['foo'];
        yield 'Boolean' => [Arr::random([true, false])];
        yield 'Array' => [['foo']];
        yield 'Integer' => [random_int(0, PHP_INT_MAX)];
        yield 'Callable' => [fn (): null => null];
        yield 'Class' => [new class {}];
    }

    #[Test]
    #[DataProvider('returnValues')]
    public function itCanBeInitialisedWithAReturnValue(mixed $returnValue): void
    {
        $instance = new SpyCallable($returnValue);

        $this->assertSame($returnValue, $instance->returnValueWhenCalled);
    }

    public static function instances(): Generator
    {
        yield 'Without return value' => [
            new SpyCallable(),
            null,
        ];

        yield 'With return value' => [
            new SpyCallable($returnValue = 'foo'),
            $returnValue,
        ];
    }

    #[Test]
    #[DataProvider('instances')]
    public function itReturnsTheSpecifiedReturnValueWhenCalled(SpyCallable $instance, mixed $expectedReturnValue): void
    {
        $returnValueWhenCalled = call_user_func($instance);

        $this->assertSame($expectedReturnValue, $returnValueWhenCalled);
    }

    #[Test]
    #[DataProvider('instances')]
    public function itSucceedsToAssertItWasCalledWhenItWas(SpyCallable $instance): void
    {
        $instance();

        $instance->assertWasCalled();
    }

    #[Test]
    #[DataProvider('instances')]
    public function itFailsToAssertItWasCalledWhenItWasNot(SpyCallable $instance): void
    {
        // Note that we don't invoke the instance here...
        $this->expectExceptionObject(new ExpectationFailedException(
            "SpyCallable was not called as expected.\nFailed asserting that an array is not empty.",
        ));

        $instance->assertWasCalled();
    }

    #[Test]
    #[DataProvider('instances')]
    public function itSucceedsToAssertItWasCalledWithArgumentsWhenItWas(SpyCallable $instance): void
    {
        $args = ['1', 2, [3], null];

        $instance(...$args);

        $instance->assertWasCalledOnceWithArguments(...$args);
    }

    #[Test]
    #[DataProvider('instances')]
    public function itSucceedsToAssertItWasCalledWithoutArgumentsWhenItWas(SpyCallable $instance): void
    {
        $instance();

        $instance->assertWasCalledOnceWithArguments();
    }

    #[Test]
    #[DataProvider('instances')]
    public function itFailsToAssertItWasCalledWithArgumentsWhenItWasCalledWithoutArguments(SpyCallable $instance): void
    {
        $args = ['1', 2, [3], null];

        $instance();

        $this->expectExceptionObject(new ExpectationFailedException(
            "SpyCallable was never called with the provided arguments.\nFailed asserting that an array is not empty.",
        ));
        $instance->assertWasCalledOnceWithArguments(...$args);
    }

    #[Test]
    #[DataProvider('instances')]
    public function itFailsToAssertItWasCalledWithArgumentsWhenTheArgumentAreDifferent(SpyCallable $instance): void
    {
        $instance('foo', 'bar');

        $this->expectExceptionObject(
            new ExpectationFailedException('SpyCallable was never called with the provided arguments.'),
        );

        $instance->assertWasCalledOnceWithArguments('bar', 'foo');
    }

    #[Test]
    #[DataProvider('instances')]
    public function itFailsToAssertItWasCalledWithArgumentsWhenItWasNot(SpyCallable $instance): void
    {
        $args = ['1', 2, [3], null];

        $instance(...$args);

        $instance->assertWasCalledOnceWithArguments(...$args);
    }

    #[Test]
    #[DataProvider('instances')]
    public function itSucceedsToAssertItWasNotCalledWhenItWasNot(SpyCallable $instance): void
    {
        // Note that we don't invoke the instance here...

        $instance->assertWasNotCalled();
    }

    #[Test]
    #[DataProvider('instances')]
    public function itFailsToAssertItWasNotCalledWhenItWas(SpyCallable $instance): void
    {
        $instance();

        $this->expectExceptionObject(new ExpectationFailedException('SpyCallable was called unexpectedly.'));

        $instance->assertWasNotCalled();
    }

    #[Test]
    #[DataProvider('instances')]
    public function itSucceedsToAssertItWasCalledOnce(SpyCallable $instance): void
    {
        $instance();

        $instance->assertWasCalledOnce();
    }
    #[Test]
    #[DataProvider('instances')]
    public function itSucceedsToAssertItWasCalledTimes(SpyCallable $instance): void
    {
        $instance();
        $instance();
        $instance();

        $instance->assertWasCalledTimes(3);
    }

    #[Test]
    public function itSucceedsToAssertItWasCalledOnceWithEqualArguments(): void
    {
        $instance = new SpyCallable();

        $instance('1');

        $instance->assertWasCalledOnceWithEqualArguments('1');
    }

    #[Test]
    public function itFailsToAssertItWasCalledOnceWithEqualArguments(): void
    {
        $instance = new SpyCallable();

        $instance('1');

        $instance->assertWasCalledOnceWithArguments(1);
        $this->expectException(ExpectationFailedException::class);
        $instance->assertWasCalledOnceWithEqualArguments(1);
    }
}