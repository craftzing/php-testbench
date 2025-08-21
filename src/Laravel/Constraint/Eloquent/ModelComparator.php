<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Eloquent;

use Illuminate\Database\Eloquent\Model;
use SebastianBergmann\Comparator\Comparator;
use SebastianBergmann\Comparator\ComparisonFailure;

use function assert;

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
        assert($expected instanceof Model);
        assert($actual instanceof Model);

        $actual->is($expected) or throw new ComparisonFailure(
            $expected,
            $actual,
            $expected->getKey(),
            $actual->getKey(),
            'Failed asserting that two Eloquent models are equal.',
        );
    }
}
