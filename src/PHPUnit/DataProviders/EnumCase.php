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
    /**
     * @var array<int, TValue>
     */
    private array $options;

    public function __construct(
        /* @var TValue */
        public UnitEnum $instance,
        /* @param array<int, TValue> ...$options */
        UnitEnum ...$options,
    ) {
        in_array($instance, $options, true) or throw new ValueError('Options should contain the given instance.');

        foreach ($options as $option) {
            $option::class === $instance::class or throw new ValueError(
                'Given options should have the same type as the given instance.',
            );
        }

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
        $differentOptions = array_filter($this->options, fn (UnitEnum $option): bool => $option !== $this->instance);

        return $differentOptions[array_rand($differentOptions)];
    }

    /**
     * @param class-string<TValue> $enumFQCN
     * @return iterable<array<self<TValue>>>
     */
    public static function cases(string $enumFQCN): iterable
    {
        foreach (new ReflectionEnum($enumFQCN)->getCases() as $case) {
            // @phpstan-ignore generator.valueType
            yield "$enumFQCN::$case->name" => [
                new self($case->getValue(), ...$enumFQCN::cases()),
            ];
        }
    }

    /**
     * @param TValue ...$options
     * @return iterable<array<self<TValue>>>
     */
    public static function options(UnitEnum ...$options): iterable
    {
        foreach ($options as $case) {
            yield "$case->name" => [new self($case, ...$options)];
        }
    }

    /**
     * @param class-string<TValue> $enumFQCN
     * @return iterable<array<self<TValue>>>
     */
    public static function except(string $enumFQCN, UnitEnum ...$except): iterable
    {
        $options = array_map(function (ReflectionEnumUnitCase $reflection) use ($except): ?UnitEnum {
            $case = $reflection->getValue();

            return match (in_array($case, $except, true)) {
                true => null,
                false => $case,
            };
        }, new ReflectionEnum($enumFQCN)->getCases());

        yield from self::options(...array_filter($options));
    }
}
