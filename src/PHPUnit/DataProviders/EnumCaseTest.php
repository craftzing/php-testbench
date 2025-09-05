<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\DataProviders;

use Craftzing\TestBench\PHPUnit\Doubles\Enums\IntBackedEnum;
use Craftzing\TestBench\PHPUnit\Doubles\Enums\StringBackedEnum;
use Craftzing\TestBench\PHPUnit\Doubles\Enums\UnitEnum;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Support\Arr;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use stdClass;
use UnitEnum as UnitEnumInterface;
use ValueError;

use function array_filter;
use function array_rand;
use function class_basename;
use function collect;
use function count;
use function iterator_to_array;

/**
 * @codeCoverageIgnore
 */
final class EnumCaseTest extends TestCase
{
    private const array ENUM_FQCNS = [
        UnitEnum::class,
        IntBackedEnum::class,
        StringBackedEnum::class,
    ];

    private Generator $faker {
        get => Factory::create();
    }

    public static function enumFQCNs(): iterable
    {
        foreach (self::ENUM_FQCNS as $enumFQCN) {
            yield class_basename($enumFQCN) => [$enumFQCN];
        }
    }

    #[Test]
    #[DataProvider('enumFQCNs')] /** @param class-string<UnitEnumInterface> $enumFQCN */
    public function itCannotConstructWhenInstanceIsNotInOptions(string $enumFQCN): void
    {
        $options = $enumFQCN::cases();
        $case = Arr::pull($options, array_rand($options));

        $this->expectException(ValueError::class);

        new EnumCase($case, ...$options);
    }

    #[Test]
    #[DataProvider('enumFQCNs')] /** @param class-string<UnitEnumInterface> $enumFQCN */
    public function itCannotConstructWhenOptionsHaveDifferentTypeComparedToGivenInstance(string $enumFQCN): void
    {
        $options = $enumFQCN::cases();
        $instance = $this->faker->randomElement($options);
        $differentEnumFQCN = $this->faker->randomElement(array_filter(
            self::ENUM_FQCNS,
            fn (string $enumFQCN): bool => $enumFQCN !== $instance::class,
        ));
        $differentEnumInstance = $this->faker->randomElement($differentEnumFQCN::cases());

        $this->expectException(ValueError::class);

        new EnumCase($instance, $differentEnumInstance, $differentEnumInstance);
    }

    #[Test]
    #[DataProvider('enumFQCNs')] /** @param class-string<UnitEnumInterface> $enumFQCN */
    public function itCanConstructWithSingleOption(string $enumFQCN): void
    {
        $options = $enumFQCN::cases();
        $instance = $options[array_rand($options)];

        $provider = new EnumCase($instance, $instance);

        $this->assertSame($instance, $provider->instance);
    }

    #[Test]
    #[DataProvider('enumFQCNs')] /** @param class-string<UnitEnumInterface> $enumFQCN */
    public function itCanConstructWithMultipleOptions(string $enumFQCN): void
    {
        $options = $enumFQCN::cases();
        $instance = $options[array_rand($options)];

        $provider = new EnumCase($instance, ...$options);

        $this->assertSame($instance, $provider->instance);
    }

    #[Test]
    #[DataProvider('enumFQCNs')] /** @param class-string<UnitEnumInterface> $enumFQCN */
    public function itCanReturnDifferentInstances(string $enumFQCN): void
    {
        $options = $enumFQCN::cases();
        $instance = $options[array_rand($options)];
        $provider = new EnumCase($instance, ...$options);

        $differentInstance = $provider->differentInstance();

        $this->assertNotEquals($instance, $differentInstance);
    }

    #[Test]
    #[DataProvider('enumFQCNs')] /** @param class-string<UnitEnumInterface> $enumFQCN */
    public function itCannotReturnDifferentInstancesWithASingleOption(string $enumFQCN): void
    {
        $options = $enumFQCN::cases();
        $instance = $options[array_rand($options)];
        $provider = new EnumCase($instance, $instance);

        $this->expectException(LogicException::class);

        $provider->differentInstance();
    }

    #[Test]
    public function itCannotProvideFromNonEnumFQCNs(): void
    {
        $this->expectException(ReflectionException::class);

        iterator_to_array(EnumCase::cases(stdClass::class));
    }

    #[Test]
    #[DataProvider('enumFQCNs')] /** @param class-string<UnitEnumInterface> $enumFQCN */
    public function itCanProvideFromEnumFQCNs(string $enumFQCN): void
    {
        $expected = $enumFQCN::cases();

        $cases = iterator_to_array(EnumCase::cases($enumFQCN));

        $this->assertCount(count($expected), $cases);
        collect($cases)->each(function (array $case) use ($expected): void {
            $this->assertInstanceOf(EnumCase::class, $case[0]);
            $this->assertContains($case[0]->instance, $expected);
        });
    }

    #[Test]
    #[DataProvider('enumFQCNs')] /** @param class-string<UnitEnumInterface> $enumFQCN */
    public function itCanProvideFromGivenOptions(string $enumFQCN): void
    {
        $expected = $enumFQCN::cases();

        $cases = iterator_to_array(EnumCase::options(...$expected));

        $this->assertCount(count($expected), $cases);
        collect($cases)->each(function (array $case) use ($expected): void {
            $this->assertInstanceOf(EnumCase::class, $case[0]);
            $this->assertContains($case[0]->instance, $expected);
        });
    }

    #[Test]
    #[DataProvider('enumFQCNs')] /** @param class-string<UnitEnumInterface> $enumFQCN */
    public function itCanProvideFromEnumFQCNsExceptSomeCases(string $enumFQCN): void
    {
        $options = $enumFQCN::cases();
        [$keep, $except] = collect($options)->shuffle()->split(2);

        $cases = iterator_to_array(EnumCase::except($enumFQCN, ...$except));

        $this->assertCount(count($keep), $cases);
        collect($cases)->each(function (array $case) use ($keep): void {
            $this->assertInstanceOf(EnumCase::class, $case[0]);
            $this->assertContains($case[0]->instance, $keep);
        });
    }
}
