<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Factories;

use BadMethodCallException;
use Illuminate\Support\Collection;
use InvalidArgumentException;

use function is_callable;

/**
 * @template TClass of object
 * @mixin ImmutableFactory<TClass>
 */
final readonly class ContainerFactory
{
    public function __construct(
        public ImmutableFactory $factory,
        public int $count,
    ) {
        if ($count < 1) {
            throw new InvalidArgumentException(
                self::class . ' must be configured to create containers with at least one instance.',
            );
        }
    }

    public function __call(string $name, array $arguments): mixed
    {
        if (! is_callable([$this->factory, $name])) {
            throw new BadMethodCallException("Method [{$name}] does not exist on " . $this->factory::class . '.');
        }

        $result = $this->factory->{$name}(...$arguments);

        if ($result instanceof ImmutableFactory) {
            return new self($result, $this->count);
        }

        return $result;
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
        return Collection::times($this->count, fn(): array => $this->factory->raw($attributes));
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
        return Collection::times($this->count, fn(): mixed => $this->factory->makeOne($attributes));
    }
}
