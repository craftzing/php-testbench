<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\DataProviders;

use ReflectionEnum;
use UnitEnum;
use ValueError;

use function array_rand;
use function array_search;
use function count;

/**
 * @template TValue of UnitEnum
 */
final class EnumCase
{
    /**
     * @var array<int, TValue>
     */
    private readonly array $options;

    private int|string $instanceKeyInOptions {
        get {
            $key = array_search($this->instance, $this->options, true);

            return match ($key) {
                false => '',
                default => $key,
            };
        }
    }

    /**
     * @param TValue $instance
     * @param array<int, TValue> ...$options
     */
    public function __construct(
        public readonly UnitEnum $instance,
        UnitEnum ...$options,
    ) {
        count($options) >= 2 or throw new ValueError('At least 2 options should should be given.');

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
        $differentOptions = $this->options;

        unset($differentOptions[$this->instanceKeyInOptions]);

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
}
