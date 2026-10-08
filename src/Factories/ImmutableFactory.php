<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Factories;

use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Support\Collection;

use function array_is_list;
use function array_map;
use function is_array;
use function is_iterable;
use function iterator_to_array;

/**
 * @template TClass of object
 * @mago-expect lint:too-many-methods
 */
abstract readonly class ImmutableFactory
{
    public Generator $faker;

    /** @param array<array-key, mixed> $state */
    final public function __construct(
        ?Generator $faker = null,
        public array $state = [],
        public int $count = 1,
    ) {
        $this->faker = $faker ?? FakerFactory::create();
    }

    /**
     * @param array<array-key, mixed> $state
     * @return static<TClass>
     */
    public function state(array $state): static
    {
        return new static($this->faker, self::merge($this->state, $state), $this->count);
    }

    /** @return static<TClass> */
    public function times(int $count): static
    {
        return new static($this->faker, $this->state, $count);
    }

    /**
     * @param array<array-key, mixed> $attributes
     * @return TClass
     */
    abstract protected function instance(array $attributes): mixed;

    /** @return array<array-key, mixed> */
    abstract public function definition(): array;

    private function resolveValue(mixed $value): mixed
    {
        if (is_iterable($value)) {
            return array_map($this->resolveValue(...), iterator_to_array($value));
        }

        if (!$value instanceof self) {
            return $value;
        }

        return match ($value->count > 1) {
            true => $value->makeMany(),
            default => $value->makeOne(),
        };
    }

    /**
     * @param array<array-key, mixed> $attributes
     * @return array<array-key, mixed>
     */
    public function raw(array $attributes = []): array
    {
        return array_map($this->resolveValue(...), self::merge(
            $this->definition(),
            $this->state,
            $attributes,
        ));
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<int, array<array-key, mixed>>
     */
    public function rawMany(array $attributes = []): array
    {
        return $this->rawCollection($attributes)->all();
    }

    /**
     * @param array<string, mixed> $attributes
     * @return Collection<int, array<array-key, mixed>>
     */
    public function rawCollection(array $attributes = []): Collection
    {
        return Collection::times($this->count, fn(): array => $this->raw($attributes));
    }

    /**
     * @param array<array-key, mixed> $attributes
     * @return TClass
     */
    public function makeOne(array $attributes = []): object
    {
        return $this->instance($this->raw($attributes));
    }

    /**
     * @param array<array-key, mixed> $attributes
     * @return array<TClass>
     */
    public function makeMany(array $attributes = []): array
    {
        return $this->makeCollection($attributes)->all();
    }

    /**
     * @param array<array-key, mixed> $attributes
     * @return Collection<int, TClass>
     */
    public function makeCollection(array $attributes = []): Collection
    {
        return Collection::times($this->count, fn(): mixed => $this->makeOne($attributes));
    }

    /**
     * @param array<array-key, mixed> $base
     * @param array<array-key, mixed> ...$replacements
     * @return array<array-key, mixed>
     */
    private static function merge(array $base, array ...$replacements): array
    {
        foreach ($replacements as $replacement) {
            foreach ($replacement as $key => $value) {
                $current = $base[$key] ?? null;
                $base[$key] = match (false) {
                    self::isMap($value), self::isMap($current) => $value,
                    default => self::merge($current, $value),
                };
            }
        }

        return $base;
    }

    private static function isMap(mixed $value): bool
    {
        return is_array($value) && !array_is_list($value);
    }
}
