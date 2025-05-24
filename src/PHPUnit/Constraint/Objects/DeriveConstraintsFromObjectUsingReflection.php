<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Constraint\Objects;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\IsEqual;
use ReflectionClass;
use ReflectionProperty;

use function array_map;

final readonly class DeriveConstraintsFromObjectUsingReflection
{
    /**
     * @return array<Constraint>
     */
    public function __invoke(object $object): array
    {
        return array_map(function (ReflectionProperty $property) use ($object): Constraint {
            return new PropertyValue($property->name, new IsEqual($property->getValue($object)));
        }, new ReflectionClass($object)->getProperties(ReflectionProperty::IS_PUBLIC));
    }
}
