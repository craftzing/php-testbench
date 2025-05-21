<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Doubles;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use stdClass;

use function call_user_func;

final class SpyCallableTest extends TestCase
{
    #[Test]
    public function itCanConstructWithoutArguments(): void
    {
        $instance = new SpyCallable();

        $this->assertNull($instance->return);
    }

    public static function returnValues(): iterable
    {
        yield 'Null' => [null];
        yield 'String' => ['foo'];
        yield 'True' => [true];
        yield 'False' => [false];
        yield 'Array' => [['foo']];
        yield 'Zero' => [0];
        yield 'Integer' => [PHP_INT_MAX];
        yield 'Callable' => [fn (): null => null];
        yield 'Class' => [new stdClass()];
    }

    #[Test]
    #[DataProvider('returnValues')]
    public function itCanConstructWithReturnValues(mixed $return): void
    {
        $instance = new SpyCallable($return);

        $this->assertSame($return, $instance->return);
    }

    public static function instances(): iterable
    {
        yield 'No return value' => [new SpyCallable(), null];
        yield 'With return value' => [new SpyCallable($return = 'foo'), $return];
    }

    #[Test]
    #[DataProvider('instances')]
    public function itReturnsGivenReturnValuesWhenCalled(SpyCallable $instance, mixed $expectedReturn): void
    {
        $returnWhenCalled = call_user_func($instance);

        $this->assertSame($expectedReturn, $returnWhenCalled);
    }

    #[Test]
    public function itCanAssertConstraints(): void
    {
        $constraint = new class extends Constraint
        {
            public function toString(): string
            {
                return 'was constrained';
            }
        };

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('was constrained.');

        new SpyCallable()->assert($constraint);
    }
}
