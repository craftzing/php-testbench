<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Factories;

use Faker\Generator;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

use function collect;
use function count;
use function mt_rand;
use function random_int;

final class ImmutableFactoryTest extends TestCase
{
    use Conditionable;

    private ImmutableFactory $instance {
        get => $this->instance ??= new class extends ImmutableFactory
        {
            public function definition(): array
            {
                return [
                    'first' => $this->faker->uuid(),
                    'last' => $this->faker->uuid(),
                ];
            }

            protected function instance(array $attributes): stdClass
            {
                return (object) $attributes;
            }
        };
    }

    #[Test]
    public function itCanConstructWithDefaults(): void
    {
        $this->assertFactoryProperties($this->instance, state: [], times: 1);
    }

    #[Test]
    public function itCanDefineState(): void
    {
        $state = ['state' => true];

        $instance = $this->instance->state($state);

        $this->assertNotEquals($this->instance, $instance);
        $this->assertFactoryProperties($this->instance, state: [], times: 1);
        $this->assertFactoryProperties($instance, state: $state, times: 1, faker: $this->instance->faker);
    }

    #[Test]
    public function itCanDefineTimes(): void
    {
        $count = random_int(1, 10);

        $instance = $this->instance->times($count);

        $this->assertNotEquals($this->instance, $instance);
        $this->assertFactoryProperties($this->instance, state: [], times: 1);
        $this->assertFactoryProperties($instance, state: [], times: $count, faker: $this->instance->faker);
    }

    #[Test]
    public function itCanReturnRawAttributes(): void
    {
        $definition = $this->instance->definition();

        $result = $this->instance->raw();

        $this->assertCount(count($definition), $result);
        $this->assertHasArrayKeyForEachDefinition($result);
    }

    #[Test]
    public function itCanReturnManyRawAttributes(): void
    {
        $instance = $this->instance->times(mt_rand(1, 10));
        $definition = $this->instance->definition();

        $results = $instance->rawMany();

        $this->assertCount($instance->count, $results);
        collect($results)->each(function (array $item) use ($definition): void {
            $this->assertCount(count($definition), $item);
            $this->assertHasArrayKeyForEachDefinition($item);
        });
    }

    #[Test]
    public function itCanReturnRawAttributesCollections(): void
    {
        $instance = $this->instance->times(mt_rand(1, 10));
        $definition = $this->instance->definition();

        $results = $instance->rawCollection();

        $this->assertCount($instance->count, $results);
        $results->each(function (array $item) use ($definition): void {
            $this->assertCount(count($definition), $item);
            $this->assertHasArrayKeyForEachDefinition($item);
        });
    }

    #[Test]
    public function itCanMakeOne(): void
    {
        $result = $this->instance->makeOne();

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertHasPropertyForEachDefinition($result);
    }

    #[Test]
    public function itCanMakeMany(): void
    {
        $instance = $this->instance->times(random_int(1, 10));

        $result = $instance->makeMany();

        $this->assertCount($instance->count, $result);
        $this->assertContainsOnlyInstancesOf(stdClass::class, $result);
    }

    #[Test]
    public function itCanMakeCollections(): void
    {
        $instance = $this->instance->times(random_int(1, 10));

        $result = $instance->makeCollection();

        $this->assertCount($instance->count, $result);
        $this->assertContainsOnlyInstancesOf(stdClass::class, $result);
    }

    public static function state(): iterable
    {
        yield [
            $attributes = [
                'last' => Str::ulid()->toString(),
                'attributes' => Str::ulid()->toString(),
            ],
            $state = [
                'first' => Str::ulid()->toString(),
                'last' => Str::ulid()->toString(),
                'state' => Str::ulid()->toString(),
            ],
            [...$state, ...$attributes],
        ];
    }

    #[Test]
    #[DataProvider('state')]
    public function itCanReturnRawAttributesWithState(array $attributes, array $state, array $expected): void
    {
        $result = $this->instance
            ->state($state)
            ->raw($attributes);

        collect($expected)->each(function (mixed $value, string $attribute) use ($result): void {
            $this->assertSame($value, $result[$attribute]);
        });
        $this->assertHasArrayKeyForEachDefinition($result);
    }

    #[Test]
    #[DataProvider('state')]
    public function itCanReturnManyRawAttributesWithState(array $attributes, array $state, array $expected): void
    {
        $results = $this->instance
            ->times(random_int(1, 10))
            ->state($state)
            ->rawMany($attributes);

        collect($results)->each(function (array $result) use ($expected): void {
            collect($expected)->each(function (mixed $value, string $attribute) use ($result): void {
                $this->assertSame($value, $result[$attribute]);
            });
            $this->assertHasArrayKeyForEachDefinition($result);
        });
    }

    #[Test]
    #[DataProvider('state')]
    public function itCanReturnRawAttributesCollectionsWithState(array $attributes, array $state, array $expected): void
    {
        $results = $this->instance
            ->times(random_int(1, 10))
            ->state($state)
            ->rawCollection($attributes);

        $results->each(function (array $result) use ($expected): void {
            collect($expected)->each(function (mixed $value, string $attribute) use ($result): void {
                $this->assertSame($value, $result[$attribute]);
            });
            $this->assertHasArrayKeyForEachDefinition($result);
        });
    }

    #[Test]
    #[DataProvider('state')]
    public function itCanMakeOneWithState(array $attributes, array $state, array $expected): void
    {
        $result = $this->instance
            ->state($state)
            ->makeOne($attributes);

        $this->assertInstanceOf(stdClass::class, $result);
        collect($expected)->each(function (mixed $value, string $attribute) use ($result): void {
            $this->assertSame($value, $result->{$attribute});
        });
        $this->assertHasPropertyForEachDefinition($result);
    }

    #[Test]
    #[DataProvider('state')]
    public function itCanMakeManyWithState(array $attributes, array $state, array $expected): void
    {
        $results = $this->instance
            ->times(random_int(1, 10))
            ->state($state)
            ->makeMany($attributes);

        $this->assertContainsOnlyInstancesOf(stdClass::class, $results);
        collect($results)->each(function (stdClass $result) use ($expected): void {
            collect($expected)->each(function (mixed $value, string $attribute) use ($result): void {
                $this->assertSame($value, $result->{$attribute});
            });
            $this->assertHasPropertyForEachDefinition($result);
        });
    }

    #[Test]
    #[DataProvider('state')]
    public function itCanMakeCollectionsWithState(array $attributes, array $state, array $expected): void
    {
        $results = $this->instance
            ->times(random_int(1, 10))
            ->state($state)
            ->makeCollection($attributes);

        $this->assertContainsOnlyInstancesOf(stdClass::class, $results);
        $results->each(function (stdClass $result) use ($expected): void {
            collect($expected)->each(function (mixed $value, string $attribute) use ($result): void {
                $this->assertSame($value, $result->{$attribute});
            });
            $this->assertHasPropertyForEachDefinition($result);
        });
    }

    public static function nestedFactories(): iterable
    {
        yield [
            fn (ImmutableFactory $instance): ImmutableFactory => $instance->state([
                'nested' => $instance->state([
                    'deeplyNested' => $instance->state(['resolved' => true]),
                ]),
                'nestedArray' => $instance->times(2)->state(['resolvedTimes' => true]),
            ]),
            function (stdClass $result): void {
                self::assertObjectHasProperty('nested', $result);
                self::assertInstanceOf(stdClass::class, $result->nested);
                self::assertObjectHasProperty('deeplyNested', $result->nested);
                self::assertInstanceOf(stdClass::class, $result->nested->deeplyNested);
                self::assertObjectHasProperty('resolved', $result->nested->deeplyNested);
                self::assertObjectHasProperty('nestedArray', $result);
                self::assertContainsOnlyInstancesOf(stdClass::class, $result->nestedArray);
                self::assertCount(2, $result->nestedArray);
                collect($result->nestedArray)->each(function (stdClass $item): void {
                    self::assertObjectHasProperty('resolvedTimes', $item);
                    self::assertTrue($item->resolvedTimes);
                });
            },
        ];
    }

    /**
     * @param callable(ImmutableFactory): ImmutableFactory $resolveInstance
     * @param callable(stdClass): void $assert
     */
    #[Test]
    #[DataProvider('nestedFactories')]
    public function itCanReturnRawAttributesWithNestedFactories(callable $resolveInstance, callable $assert): void
    {
        $instance = $resolveInstance($this->instance);

        $result = $instance->raw();

        $assert((object) $result);
    }

    /**
     * @param callable(ImmutableFactory): ImmutableFactory $resolveInstance
     * @param callable(stdClass): void $assert
     */
    #[Test]
    #[DataProvider('nestedFactories')]
    public function itCanReturnManyRawAttributesWithNestedFactories(callable $resolveInstance, callable $assert): void
    {
        $instance = $resolveInstance($this->instance);

        $results = $instance->rawMany();

        collect($results)->each(function (array $result) use ($assert): void {
            $assert((object) $result);
        });
    }

    /**
     * @param callable(ImmutableFactory): ImmutableFactory $resolveInstance
     * @param callable(stdClass): void $assert
     */
    #[Test]
    #[DataProvider('nestedFactories')]
    public function itCanReturnRawAttributesCollectionsWithNestedFactories(
        callable $resolveInstance,
        callable $assert,
    ): void {
        $instance = $resolveInstance($this->instance);

        $results = $instance->rawCollection();

        $results->each(function (array $result) use ($assert): void {
            $assert((object) $result);
        });
    }

    /**
     * @param callable(ImmutableFactory): ImmutableFactory $resolveInstance
     * @param callable(stdClass): void $assert
     */
    #[Test]
    #[DataProvider('nestedFactories')]
    public function itCanMakeOneWithNestedFactories(callable $resolveInstance, callable $assert): void
    {
        $instance = $resolveInstance($this->instance);

        $result = $instance->makeOne();

        $assert($result);
    }

    /**
     * @param callable(ImmutableFactory): ImmutableFactory $resolveInstance
     * @param callable(stdClass): void $assert
     */
    #[Test]
    #[DataProvider('nestedFactories')]
    public function itCanMakeManyWithNestedFactories(callable $resolveInstance, callable $assert): void
    {
        $instance = $resolveInstance($this->instance);

        $results = $instance->makeMany();

        collect($results)->each($assert(...));
    }

    /**
     * @param callable(ImmutableFactory): ImmutableFactory $resolveInstance
     * @param callable(stdClass): void $assert
     */
    #[Test]
    #[DataProvider('nestedFactories')]
    public function itCanMakeCollectionsWithNestedFactories(callable $resolveInstance, callable $assert): void
    {
        $instance = $resolveInstance($this->instance);

        $results = $instance->makeCollection();

        $results->each($assert(...));
    }

    private function assertFactoryProperties(
        ImmutableFactory $factory,
        array $state,
        int $times,
        ?Generator $faker = null,
    ): void {
        $this->assertInstanceOf(Generator::class, $factory->faker);
        $this->when($faker !== null, function () use ($factory, $faker): void {
            $this->assertSame($faker, $factory->faker);
        });
        $this->assertSame($state, $factory->state);
        $this->assertSame($times, $factory->count);
    }

    private function assertHasPropertyForEachDefinition(stdClass $result): void
    {
        foreach ($this->instance->definition() as $attribute => $value) {
            self::assertObjectHasProperty($attribute, $result);
        }
    }

    private function assertHasArrayKeyForEachDefinition(array $result): void
    {
        foreach ($this->instance->definition() as $attribute => $value) {
            self::assertArrayHasKey($attribute, $result);
        }
    }
}
