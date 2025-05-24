<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Constraint\Objects;

use PHPUnit\Framework\Constraint\Constraint;

use function is_string;

trait DerivesConstraintsFromObjects
{
    private static ?DeriveConstraintsFromObject $deriveConstraintsFromObject = null;

    /**
     * @var array<Constraint>
     */
    public readonly array $objectConstraints;

    /**
     * @return array<Constraint>
     */
    public function givenOrDerivedObjectConstraints(string|object $expected): array
    {
        if ($this->objectConstraints !== []) {
            return $this->objectConstraints;
        }

        if (is_string($expected)) {
            return [];
        }

        return (self::$deriveConstraintsFromObject ?: new DeriveConstraintsFromObjectUsingReflection())($expected);
    }

    public static function deriveConstraintsFromObjectUsing(
        ?DeriveConstraintsFromObject $deriveConstraintsFromObject,
    ): void {
        self::$deriveConstraintsFromObject = $deriveConstraintsFromObject;
    }
}
