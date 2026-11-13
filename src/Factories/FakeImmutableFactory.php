<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Factories;

use stdClass;

/** @extends ImmutableFactory<stdClass> */
final readonly class FakeImmutableFactory extends ImmutableFactory
{
    protected function instance(array $attributes): stdClass
    {
        return (object) $attributes;
    }

    public function definition(): array
    {
        return [];
    }
}
