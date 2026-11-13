<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Faker\Providers;

use Craftzing\TestBench\Doubles\Enum\UnitEnum;
use Faker\Factory;
use Faker\Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

use function array_map;

final class CycleTest extends TestCase
{
    private Generator $faker {
        get => $this->faker ??= Factory::create();
    }

    public static function lessThanTwoOptions(): iterable
    {
        yield 'Empty list' => [[]];
        yield 'List with only 1 option' => [[1]];
    }

    #[Test]
    #[DataProvider('lessThanTwoOptions')]
    public function itCannotCycleThroughListsWithLessThanTwoOptions(array $options): void
    {
        $instance = new Cycle($this->faker);

        $this->expectException(InvalidArgumentException::class);

        $instance->cycle($options);
    }

    public static function options(): iterable
    {
        yield 'Integers' => [
            [10, 20, 30],
            [10, 20, 30, 10, 20],
        ];

        yield 'Strings' => [
            ['craftzing', 'itsme'],
            ['craftzing', 'itsme', 'craftzing', 'itsme'],
        ];

        yield 'Enums' => [
            [UnitEnum::One, UnitEnum::Three, UnitEnum::Two],
            [UnitEnum::One, UnitEnum::Three, UnitEnum::Two, UnitEnum::One],
        ];

        yield 'Class instances' => [
            [$first = new stdClass(), $last = new stdClass()],
            [$first, $last, $first, $last, $first],
        ];

        yield 'Mixed type options' => [
            ['first', UnitEnum::One, $class = new stdClass()],
            ['first', UnitEnum::One, $class, 'first', UnitEnum::One, $class],
        ];
    }

    #[Test]
    #[DataProvider('options')]
    public function itCyclesThroughOptions(array $options, array $expected): void
    {
        $instance = new Cycle($this->faker);

        $results = array_map(static fn() => $instance->cycle($options), $expected);

        $this->assertSame($expected, $results);
    }

    #[Test]
    public function itCyclesThroughDifferentOptionLists(): void
    {
        $firstOptionList = ['a', 'b', 'c', 'd', 'e'];
        $secondOptionList = [1, 2, 3];
        $lastOptionList = UnitEnum::cases();
        $instance = new Cycle($this->faker);

        $results = [
            $instance->cycle($firstOptionList),
            $instance->cycle($firstOptionList),
            $instance->cycle($secondOptionList),
            $instance->cycle($firstOptionList),
            $instance->cycle($secondOptionList),
            $instance->cycle($lastOptionList),
            $instance->cycle($firstOptionList),
            $instance->cycle($secondOptionList),
            $instance->cycle($lastOptionList),
            $instance->cycle($lastOptionList),
            $instance->cycle($secondOptionList),
            $instance->cycle($firstOptionList),
            $instance->cycle($secondOptionList),
            $instance->cycle($firstOptionList),
            $instance->cycle($firstOptionList),
        ];

        $this->assertSame(
            [
                'a',
                'b',
                1,
                'c',
                2,
                UnitEnum::One,
                'd',
                3,
                UnitEnum::Two,
                UnitEnum::Three,
                1,
                'e',
                2,
                'a',
                'b',
            ],
            $results,
        );
    }

    #[Test]
    #[DataProvider('options')]
    public function itCanBeAddedAsProviderToFakerFactories(array $options, array $expected): void
    {
        $this->faker->addProvider(new Cycle($this->faker));

        $results = array_map(fn() => $this->faker->cycle($options), $expected);

        $this->assertSame($expected, $results);
    }
}
