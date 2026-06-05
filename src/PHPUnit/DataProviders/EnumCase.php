<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\DataProviders;

use InvalidArgumentException;
use LogicException;
use ReflectionEnum;
use ReflectionEnumUnitCase;
use UnitEnum;
use ValueError;

use function array_filter;
use function array_map;
use function array_rand;
use function count;
use function in_array;

/** @template TValue of UnitEnum */
final readonly class EnumCase
{
    /** @var array<array-key, TValue> */
    private array $options;

    /**
     * @param TValue $instance
     * @param TValue ...$options
     */
    public function __construct(
        public UnitEnum $instance,
        UnitEnum ...$options,
    ) {
        if (in_array($instance, $options, strict: true) === false) {
            throw new ValueError('Options should contain the given instance.');
        }

        foreach ($options as $option) {
            if ($option::class !== $instance::class) {
                throw new ValueError(
                    'Given options should have the same type as the given instance.',
                );
            }
        }

        $this->options = $options;
    }

    /** @return TValue */
    public function differentInstance(): UnitEnum
    {
        if (count($this->options) <= 1) {
            throw new LogicException(
                self::class . ' was configured with a single option and can therefore not return a different instance.',
            );
        }

        $differentOptions = array_filter($this->options, fn(UnitEnum $option): bool => $option !== $this->instance);

        return $differentOptions[array_rand($differentOptions)];
    }

    /**
     * @param class-string<TValue> $enumFQCN
     * @return iterable<string, list<self<TValue>>>
     */
    public static function cases(string $enumFQCN): iterable
    {
        if (!enum_exists($enumFQCN)) {
            throw new InvalidArgumentException("Expected a concrete Enum class string, got: {$enumFQCN}");
        }

        foreach (new ReflectionEnum($enumFQCN)->getCases() as $case) {
            // @mago-expect analyzer:invalid-yield-value-type
            yield "{$enumFQCN}::{$case->name}" => [
                // @mago-expect analyzer:possibly-static-access-on-interface
                new self($case->getValue(), ...$enumFQCN::cases()),
            ];
        }
    }

    /**
     * @param TValue ...$options
     * @return iterable<string, list<self<TValue>>>
     */
    public static function options(UnitEnum ...$options): iterable
    {
        foreach ($options as $case) {
            yield $case->name => [new self($case, ...$options)];
        }
    }

    /**
     * @param class-string<TValue> $enumFQCN
     * @return iterable<string, list<self<TValue>>>
     */
    public static function except(string $enumFQCN, UnitEnum ...$except): iterable
    {
        /** @var TValue[] $options */
        $options = array_map(static function (ReflectionEnumUnitCase $reflection) use ($except): ?UnitEnum {
            $case = $reflection->getValue();

            return match (in_array($case, $except, strict: true)) {
                true => null,
                false => $case,
            };
        }, new ReflectionEnum($enumFQCN)->getCases());

        yield from self::options(...array_filter($options));
    }
}
