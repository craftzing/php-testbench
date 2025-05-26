<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Constraint\Objects;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Framework\TestCase;

final class DeriveConstraintsFromObjectUsingReflectionTest extends TestCase
{
    #[Test]
    public function itShouldNotDeriveConstraintsFromNonPublicProperties(): void
    {
        $object = new readonly class
        {
            public function __construct(
                protected mixed $protected = 'protected',
                protected mixed $private = 'private',
            ) {}
        };

        $results = new DeriveConstraintsFromObjectUsingReflection()->__invoke($object);

        $this->assertEmpty($results);
    }

    #[Test]
    public function itCanDeriveConstraintsFromPublicProperties(): void
    {
        $value = 'SomePublicValue';
        $object = new readonly class ($value)
        {
            public function __construct(
                public mixed $somePublicProperty,
            ) {}
        };

        $results = new DeriveConstraintsFromObjectUsingReflection()->__invoke($object);

        $this->assertEquals([
            new PropertyValue('somePublicProperty', new IsEqual($value)),
        ], $results);
    }
}
