<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Concerns;

use Faker\Factory;
use Faker\Generator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

use function array_keys;
use function in_array;

/**
 * @mixin TestCase
 */
trait FakesData
{
    protected Generator $faker;

    #[Before]
    public function setupFaker(string $locale = 'nl_BE'): void
    {
        $this->faker = self::faker($locale);
    }

    #[After]
    public function unsetFaker(): void
    {
        unset($this->faker);
    }

    private static function faker(string $locale = 'nl_BE'): Generator
    {
        return Factory::create($locale);
    }

    /**
     * @template T of mixed
     * @param array<T> ...$options
     * @return T
     */
    private static function random(mixed ...$options): mixed
    {
        return Arr::random($options);
    }

    /**
     * @template T of mixed
     * @param T|array<T> $exclude
     * @param array<T> ...$options
     * @return T
     */
    private static function randomExcept(mixed $exclude, mixed ...$options): mixed
    {
        return Collection::make($options)
            ->filter(fn(mixed $value): bool => in_array($value, Arr::wrap($exclude)) === false)
            ->random();
    }

    private static function randomHttpStatus(): int
    {
        return self::random(...array_keys(SymfonyResponse::$statusTexts));
    }
}
