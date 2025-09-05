<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Bus;

use Craftzing\TestBench\PHPUnit\Doubles\SpyCallable;
use Illuminate\Support\Facades\Bus;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\ExpectationFailedException;
use stdClass;

final class HasHandlerTest extends TestCase
{
    #[Test]
    #[TestWith([true], 'Boolean')]
    #[TestWith([1], 'Integers')]
    #[TestWith([['event']], 'Array')]
    public function itCannotEvaluateUnsupportedValueTypes(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(HasHandler::class . ' can only be evaluated for strings');

        $this->assertThat($value, new HasHandler('SomeHandlerClassFCN'));
    }

    #[Test]
    public function itCannotEvaluateStringThatAreNotExistingClasses(): void
    {
        $value = 'NotAClass';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(HasHandler::class . " can only be evaluated for existing classes, got $value.");

        $this->assertThat($value, new HasHandler('SomeHandlerClassFCN'));
    }

    #[Test]
    public function itFailsWhenNoHandlerIsMapped(): void
    {
        $messageClassFCN = stdClass::class;

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('has handler');
        $this->expectExceptionMessage('stdClass has no handler mapped to it');

        $this->assertThat($messageClassFCN, new HasHandler('SomeHandlerClassFCN'));
    }

    #[Test]
    public function itFailsWhenDifferentHandlerIsMapped(): void
    {
        $messageClassFCN = stdClass::class;
        Bus::map([$messageClassFCN => SpyCallable::class]);

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('has handler');
        $this->expectExceptionMessage('stdClass has a different handler mapped to it');

        $this->assertThat($messageClassFCN, new HasHandler('SomeHandlerClassFCN'));
    }

    #[Test]
    public function itPassesWhenGivenHandlerIsMapped(): void
    {
        $messageClassFCN = stdClass::class;
        Bus::map([$messageClassFCN => SpyCallable::class]);

        $this->assertThat($messageClassFCN, new HasHandler(SpyCallable::class));
    }
}
