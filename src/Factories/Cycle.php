<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Factories;

use function count;

final readonly class Cycle implements DeferredAttribute
{
    /** @var non-empty-array<array-key, mixed> */
    private array $options;

    public function __construct(mixed $first, mixed $second, mixed ...$options)
    {
        $this->options = [$first, $second, ...$options];
    }

    public function __invoke(ImmutableFactory $factory): mixed
    {
        // @mago-expect analyzer:too-few-arguments
        return $factory->faker->cycle(...$this->options);
    }

    public function count(): int
    {
        return count($this->options);
    }
}
