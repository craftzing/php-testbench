<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Constraint;

use Craftzing\TestBench\PHPUnit\TestFixture\IsImmutable\MutableObject;
use Craftzing\TestBench\PHPUnit\TestFixture\IsImmutable\ValueObject;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class IsImmutableTest extends TestCase
{
    public function testCanBeRepresentedAsString(): void
    {
        $this->assertSame('is immutable', IsImmutable::comparedTo(new ValueObject(1))->toString());
    }

    public function testIsCountable(): void
    {
        $this->assertCount(1, IsImmutable::comparedTo(new ValueObject(1)));
    }

    public function testRejectsADifferentObjectThanTheOneUnderTest(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Cannot compare instances of different classes for immutability.');

        IsImmutable::comparedTo(new ValueObject(1))->evaluate(new MutableObject(1));
    }

    public function testRejectsObjectsThatAreNotImmutable(): void
    {
        $instance = new MutableObject(1);
        $other = $instance->change(1);
        $this->expectException(ExpectationFailedException::class);

        IsImmutable::comparedTo($instance)->evaluate($other);
    }

    public function testAcceptsObjectsThatAreImmutable(): void
    {
        $originalInstance = new ValueObject(1);

        $this->assertThat($originalInstance, IsImmutable::comparedTo($originalInstance->change(1)));
    }

    public function testCanIncludeAdditionalAssertions(): void
    {
        $originalInstance = new ValueObject(1);

        $this->assertThat($originalInstance, IsImmutable::comparedTo($originalInstance->change(1), function (
            ValueObject $instance,
            ValueObject $other,
        ) use ($originalInstance): void {
            $this->assertTrue($originalInstance->equals($other));
        }));
    }
}
