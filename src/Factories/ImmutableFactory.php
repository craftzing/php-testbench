<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Factories;

use Closure;
use Craftzing\TestBench\Faker\Providers\Cycle;
use Faker\Factory as FakerFactory;
use Faker\Generator;

use function array_any;
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
    /** @var Generator&Cycle */
    public Generator $faker;

    /** @param array<array-key, mixed> $state */
    final public function __construct(
        ?Generator $faker = null,
        public array $state = [],
    ) {
        if ($faker === null) {
            $faker = FakerFactory::create();
        }

        if (!self::hasCycleProvider($faker)) {
            $faker->addProvider(new Cycle($faker));
        }

        /** @var Generator&Cycle $faker */
        $this->faker = $faker;
    }

    private static function hasCycleProvider(Generator $faker): bool
    {
        return array_any($faker->getProviders(), static fn($provider) => $provider instanceof Cycle);
    }

    /**
     * @param array<array-key, mixed> $state
     * @return static<TClass>
     */
    public function state(array $state): static
    {
        return new static($this->faker, self::merge($this->state, $state));
    }

    /** @return ContainerFactory<TClass> */
    public function times(int $count): ContainerFactory
    {
        return new ContainerFactory($this, $count);
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
        if ($value instanceof Closure) {
            return $this->resolveValue($value($this));
        }

        if (is_iterable($value)) {
            return array_map($this->resolveValue(...), iterator_to_array($value));
        }

        if ($value instanceof ContainerFactory) {
            return $value->makeMany();
        }

        if ($value instanceof self) {
            return $value->makeOne();
        }

        return $value;
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
     * @param array<array-key, mixed> $attributes
     * @return TClass
     */
    public function makeOne(array $attributes = []): object
    {
        return $this->instance($this->raw($attributes));
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
