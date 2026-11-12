<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Faker;

use Craftzing\TestBench\Doubles\Enum\UnitEnum;
use Faker\Factory;
use Faker\Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use stdClass;

use function array_reduce;

final class CycleProviderTest extends TestCase
{
    private Generator $faker {
        get => Factory::create();
    }

    #[Test]
    #[TestWith([[]], 'Empty list')]
    #[TestWith([[1]], 'List with only 1 option')]
    public function itCannotCycleThroughListsWithLessThanTwoOptions(array $options): void
    {
        $instance = new CycleProvider($this->faker);

        $this->expectException(InvalidArgumentException::class);

        $instance->cycle($options);
    }

    #[Test]
    #[TestWith([[10, 20, 30], [10, 20, 30, 10, 20]])]
    #[TestWith([['craftzing', 'itsme'], ['craftzing', 'itsme', 'craftzing', 'itsme']])]
    #[TestWith([[UnitEnum::One, UnitEnum::Three, UnitEnum::Two], [UnitEnum::One, UnitEnum::Three, UnitEnum::Two, UnitEnum::One]])]
    public function itCyclesThroughOptionsOfTheSameType(array $options, array $expected): void
    {
        $instance = new CycleProvider($this->faker);

        $results = array_reduce($expected, static function (array $results) use ($instance, $options): array {
            $results[] = $instance->cycle($options);

            return $results;
        }, []);

        $this->assertSame($expected, $results);
    }

    #[Test]
    public function itCyclesThroughOptionsOfDifferentTypes(): void
    {
        $options = ['first', UnitEnum::One, $class = new stdClass()];
        $expected = ['first', UnitEnum::One, $class, 'first', UnitEnum::One, $class];
        $instance = new CycleProvider($this->faker);

        $results = array_reduce($expected, static function (array $results) use ($instance, $options): array {
            $results[] = $instance->cycle($options);

            return $results;
        }, []);

        $this->assertSame($expected, $results);
    }

    #[Test]
    public function itCyclesThroughDifferentOptionLists(): void
    {
        $firstOptionList = ['a', 'b', 'c', 'd', 'e'];
        $secondOptionList = [1, 2, 3];
        $lastOptionList = UnitEnum::cases();
        $instance = new CycleProvider($this->faker);

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
}
