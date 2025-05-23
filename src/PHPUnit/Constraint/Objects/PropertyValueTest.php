<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Constraint\Objects;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Framework\Constraint\IsIdentical;
use PHPUnit\Framework\Constraint\IsTrue;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use stdClass;

final class PropertyValueTest extends TestCase
{
    #[Test]
    #[TestWith([new IsEqual('someValue'), 'is equal'])]
    #[TestWith([new IsIdentical('someValue'), 'is identical'])]
    public function itCanConstruct(Constraint $constraint, string $toString): void
    {
        $name = 'propertyName';

        $instance = new PropertyValue($name, $constraint);

        $this->assertSame($name, $instance->name);
        $this->assertSame($constraint, $instance->constraint);
        $this->assertStringContainsString($toString, $instance->toString());
    }

    #[Test]
    #[TestWith([true], 'Boolean')]
    #[TestWith([1], 'Integers')]
    #[TestWith([['event']], 'Array')]
    public function itFailsWhenEvaluatingNonObjects(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('can only be evaluated for objects.');

        $this->assertThat($value, new PropertyValue('propertyName', new IsTrue()));
    }

    #[Test]
    public function itFailsWhenPropertiesDontExist(): void
    {
        $object = new stdClass();

        $this->expectException(ExpectationFailedException::class);

        $this->assertThat($object, new PropertyValue('propertyName', new IsTrue()));
    }

    #[Test]
    public function itFailsWhenPropertiesDontMatchGivenConstraints(): void
    {
        $constraint = new Callback(fn (): bool => false);
        $object = $this->objectWithIdProperty();

        $this->expectException(ExpectationFailedException::class);

        $this->assertThat($object, new PropertyValue('id', $constraint));
    }

    #[Test]
    public function itPassesWhenPropertiesMatchGivenConstraints(): void
    {
        $constraint = new Callback(fn (): bool => true);
        $object = $this->objectWithIdProperty();

        $this->assertThat($object, new PropertyValue('id', $constraint));
    }

    /**
     * @return object{id: string}
     */
    private function objectWithIdProperty(): object
    {
        return new class
        {
            public function __construct(
                public string $id = 'SomeId',
            ) {}
        };
    }
}
