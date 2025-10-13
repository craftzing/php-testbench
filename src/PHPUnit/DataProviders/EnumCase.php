<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\DataProviders;

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

/**
 * @template TValue of UnitEnum
 */
final readonly class EnumCase
{
    /** @var TValue */
    public UnitEnum $instance;

    /** @var array<int, TValue> */
    private array $options;

    /**
     * @param TValue $instance
     * @param TValue ...$options
     */
    public function __construct(UnitEnum $instance, UnitEnum ...$options)
    {
        in_array($instance, $options, true) or throw new ValueError('Options should contain the given instance.');

        foreach ($options as $option) {
            $option::class === $instance::class or throw new ValueError(
                'Given options should have the same type as the given instance.',
            );
        }

        $this->instance = $instance;
        $this->options = $options;
    }

    /**
     * @return TValue
     */
    public function differentInstance(): UnitEnum
    {
        count($this->options) > 1 or throw new LogicException(
            self::class . ' was configured with a single option and can therefore not return a different instance.',
        );

        $differentOptions = array_filter(
            $this->options,
            fn (UnitEnum $option): bool => $option !== $this->instance,
        );

        return $differentOptions[array_rand($differentOptions)];
    }

    /**
     * @param class-string<TValue> $enumFQCN
     * @return iterable<array{self<TValue>}>
     */
    public static function cases(string $enumFQCN): iterable
    {
        /** @var ReflectionEnumUnitCase $case */
        foreach (new ReflectionEnum($enumFQCN)->getCases() as $case) {
            /** @var TValue $value */
            $value = $case->getValue();
            /** @var array<int, TValue> $all */
            $all = $enumFQCN::cases();
            /** @var self<TValue> $enumCase */
            $enumCase = new self($value, ...$all);

            yield "$enumFQCN::{$case->name}" => [$enumCase];
        }
    }

    /**
     * @param TValue ...$options
     * @return iterable<array{self<TValue>}>
     */
    public static function options(UnitEnum ...$options): iterable
    {
        /** @var array<int, TValue> $options */
        foreach ($options as $case) {
            /** @var TValue $case */
            /** @var self<TValue> $enumCase */
            $enumCase = new self($case, ...$options);
            yield "{$case->name}" => [$enumCase];
        }
    }

    /**
     * @param class-string<TValue> $enumFQCN
     * @param TValue ...$except
     * @return iterable<array{self<TValue>}>
     */
    public static function except(string $enumFQCN, UnitEnum ...$except): iterable
    {
        /** @var class-string<TValue> $enumFQCN */
        /** @var array<int, TValue> $except */
        $options = array_map(
            function (ReflectionEnumUnitCase $reflection) use ($except): ?UnitEnum {
                $case = $reflection->getValue();

                return in_array($case, $except, true) ? null : $case;
            },
            new ReflectionEnum($enumFQCN)->getCases(),
        );

        /** @var array<int, TValue|null> $options */
        /** @var array<int, TValue> $filtered */
        $filtered = array_filter($options);

        yield from self::options(...$filtered);
    }
}