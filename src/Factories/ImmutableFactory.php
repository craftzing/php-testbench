<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Factories;

use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Support\Collection;

use function array_map;
use function is_iterable;
use function iterator_to_array;

/**
 * @template TClass of object
 */
abstract class ImmutableFactory
{
    /**
     * @param array<string, mixed> $state
     */
    final public function __construct(
        public ?Generator $faker = null,
        public readonly array $state = [],
        public readonly int $count = 1,
    ) {
        $this->faker = $faker ?? FakerFactory::create();
    }

    /**
     * @param array<string, mixed> $state
     * @return static
     */
    public function state(array $state): static
    {
        /** @var static<TClass> $next */
        $next = new static($this->faker, [...$this->state, ...$state], $this->count);

        return $next;
    }

    /**
     * @return static
     */
    public function times(int $count): static
    {
        /** @var static<TClass> $next */
        $next = new static($this->faker, $this->state, $count);

        return $next;
    }

    /**
     * @return array<string, mixed>
     */
    abstract public function definition(): array;

    /**
     * @param array<string, mixed> $attributes
     * @return TClass
     */
    abstract protected function instance(array $attributes): object;

    private function resolveValue(mixed $value): mixed
    {
        if (is_iterable($value)) {
            return array_map($this->resolveValue(...), iterator_to_array($value));
        }

        if (! $value instanceof self) {
            return $value;
        }

        return $value->count > 1
            ? $value->makeMany()
            : $value->makeOne();
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public function raw(array $attributes = []): array
    {
        return array_map($this->resolveValue(...), [
            ...$this->definition(),
            ...$this->state,
            ...$attributes,
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return list<array<string, mixed>>
     */
    public function rawMany(array $attributes = []): array
    {
        return $this->rawCollection($attributes)->all();
    }

    /**
     * @param array<string, mixed> $attributes
     * @return Collection<int, array<string, mixed>>
     */
    public function rawCollection(array $attributes = []): Collection
    {
        return Collection::times($this->count, fn (): array => $this->raw($attributes));
    }

    /**
     * @param array<string, mixed> $attributes
     * @return TClass
     */
    public function makeOne(array $attributes = []): object
    {
        return $this->instance($this->raw($attributes));
    }

    /**
     * @param array<string, mixed> $attributes
     * @return list<TClass>
     */
    public function makeMany(array $attributes = []): array
    {
        return $this->makeCollection($attributes)->all();
    }

    /**
     * @param array<string, mixed> $attributes
     * @return Collection<int, TClass>
     */
    public function makeCollection(array $attributes = []): Collection
    {
        return Collection::times($this->count, fn (): object => $this->makeOne($attributes));
    }
}