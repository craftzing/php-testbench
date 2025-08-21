<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Eloquent;

use AssertionError;
use Illuminate\Database\Eloquent\Model;
use SebastianBergmann\Comparator\Comparator;
use SebastianBergmann\Comparator\ComparisonFailure;

final class ModelComparator extends Comparator
{
    public function accepts(mixed $expected, mixed $actual): bool
    {
        return $expected instanceof Model && $actual instanceof Model;
    }

    public function assertEquals(
        mixed $expected,
        mixed $actual,
        float $delta = 0.0,
        bool $canonicalize = false,
        bool $ignoreCase = false,
    ): void {
        $expected instanceof Model or throw self::notInstanceOfModel('expected', $expected);
        $actual instanceof Model or throw self::notInstanceOfModel('actual', $actual);

        $actual->is($expected) or throw new ComparisonFailure(
            $expected,
            $actual,
            $expected->getKey(),
            $actual->getKey(),
            'Failed asserting that two Eloquent models are equal.',
        );
    }

    private static function notInstanceOfModel(string $argumentName, mixed $value): AssertionError
    {
        return new AssertionError(
            "Argument $argumentName must be an instance of " . Model::class . ', received ' . gettype($value) . '.',
        );
    }
}
