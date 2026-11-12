<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Factories;

use Craftzing\TestBench\Faker\Providers\Cycle;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

use function array_filter;
use function array_keys;
use function collect;
use function count;
use function mt_rand;
use function random_int;

final class ImmutableFactoryTest extends TestCase
{
    use Conditionable;

    #[Test]
    public function itCanConstructWithDefaults(): void
    {
        $instance = new FakeImmutableFactory();

        $this->assertFactoryProperties($instance, state: [], times: 1);
    }

    #[Test]
    public function itCanEnableCycleProviderOnDefaultFakerInstances(): void
    {
        $instance = new FakeImmutableFactory();

        $cycleProviders = array_filter(
            $instance->faker->getProviders(),
            static fn($provider) => $provider instanceof Cycle,
        );

        $this->assertCount(1, $cycleProviders);
    }

    #[Test]
    public function itCanEnableCycleProviderOnGivenFakerInstances(): void
    {
        $faker = Factory::create();
        new FakeImmutableFactory($faker);

        $cycleProviders = array_filter(
            $faker->getProviders(),
            static fn($provider) => $provider instanceof Cycle,
        );

        $this->assertCount(1, $cycleProviders);
    }

    #[Test]
    public function itDoesNotEnableCycleProviderOnGivenFakerInstancesWhenAlreadyEnabled(): void
    {
        $faker = Factory::create();
        $faker->addProvider(new Cycle($faker));
        new FakeImmutableFactory($faker);

        $cycleProviders = array_filter(
            $faker->getProviders(),
            static fn($provider) => $provider instanceof Cycle,
        );

        $this->assertCount(1, $cycleProviders);
    }

    #[Test]
    public function itCanDefineState(): void
    {
        $originalInstance = new FakeImmutableFactory();
        $state = ['state' => true];

        $instance = $originalInstance->state($state);

        $this->assertNotEquals($originalInstance, $instance);
        $this->assertFactoryProperties($originalInstance, state: [], times: 1);
        $this->assertFactoryProperties($instance, state: $state, times: 1, faker: $originalInstance->faker);
    }

    #[Test]
    public function itCanDefineTimes(): void
    {
        $originalInstance = new FakeImmutableFactory();
        $count = random_int(2, 10);

        $instance = $originalInstance->times($count);

        $this->assertNotEquals($originalInstance, $instance);
        $this->assertFactoryProperties($originalInstance, state: [], times: 1);
        $this->assertFactoryProperties($instance, state: [], times: $count, faker: $originalInstance->faker);
    }

    #[Test]
    public function itCanReturnRawAttributes(): void
    {
        $instance = new FakeImmutableFactory();
        $definition = $instance->definition();

        $result = $instance->raw();

        $this->assertCount(count($definition), $result);
        $this->assertHasArrayKeyForEachDefinition($result);
    }

    #[Test]
    public function itCanReturnManyRawAttributes(): void
    {
        $instance = new FakeImmutableFactory()->times(mt_rand(1, 10));
        $definition = $instance->definition();

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
        $instance = new FakeImmutableFactory()->times(mt_rand(1, 10));
        $definition = $instance->definition();

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
        $instance = new FakeImmutableFactory();

        $result = $instance->makeOne();

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertHasPropertyForEachDefinition($result);
    }

    #[Test]
    public function itCanMakeMany(): void
    {
        $instance = new FakeImmutableFactory()->times(random_int(1, 10));

        $result = $instance->makeMany();

        $this->assertCount($instance->count, $result);
        $this->assertContainsOnlyInstancesOf(stdClass::class, $result);
    }

    #[Test]
    public function itCanMakeCollections(): void
    {
        $instance = new FakeImmutableFactory()->times(random_int(1, 10));

        $result = $instance->makeCollection();

        $this->assertCount($instance->count, $result);
        $this->assertContainsOnlyInstancesOf(stdClass::class, $result);
    }

    public static function state(): iterable
    {
        yield 'Flat merge' => [
            $attributes = [
                'last' => Str::ulid()->toBase32(),
                'attributes' => Str::ulid()->toBase32(),
            ],
            $state = [
                'first' => Str::ulid()->toBase32(),
                'last' => Str::ulid()->toBase32(),
                'state' => Str::ulid()->toBase32(),
            ],
            [...$state, ...$attributes],
        ];

        yield 'Recursive merge' => [
            [
                'config' => [
                    'attribute_only' => true,
                    'shared' => 'from_attribute',
                ],
            ],
            [
                'config' => [
                    'state_only' => true,
                    'shared' => 'from_state',
                ],
            ],
            [
                'config' => [
                    'state_only' => true,
                    'shared' => 'from_attribute',
                    'attribute_only' => true,
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('state')]
    public function itCanReturnRawAttributesWithState(array $attributes, array $state, array $expected): void
    {
        $result = new FakeImmutableFactory()->state($state)->raw($attributes);

        collect($expected)->each(function (mixed $value, string $attribute) use ($result): void {
            $this->assertSame($value, $result[$attribute]);
        });
        $this->assertHasArrayKeyForEachDefinition($result);
    }

    #[Test]
    #[DataProvider('state')]
    public function itCanReturnManyRawAttributesWithState(array $attributes, array $state, array $expected): void
    {
        $results = new FakeImmutableFactory()
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
        $results = new FakeImmutableFactory()
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
        $result = new FakeImmutableFactory()->state($state)->makeOne($attributes);

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
        $results = new FakeImmutableFactory()
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
        $results = new FakeImmutableFactory()
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

    #[Test]
    public function itMergesSequentialStateCallsRecursively(): void
    {
        $instance = new FakeImmutableFactory()
            ->state([
                'settings' => [
                    'theme' => 'light',
                    'notifications' => true,
                ],
            ])
            ->state([
                'settings' => [
                    'theme' => 'dark',
                    'timezone' => 'UTC',
                ],
            ]);

        $this->assertSame(
            [
                'theme' => 'dark',
                'notifications' => true,
                'timezone' => 'UTC',
            ],
            $instance->state['settings'],
        );
    }

    #[Test]
    public function itRecursivelyMergesDefinitionStateAndAttributes(): void
    {
        $factory = new readonly class extends ImmutableFactory {
            protected function instance(array $attributes): stdClass
            {
                return (object) $attributes;
            }

            public function definition(): array
            {
                return [
                    'nested' => [
                        'from_definition' => true,
                        'overridden_by_state' => false,
                        'overridden_by_attributes' => false,
                    ],
                ];
            }
        };

        $result = $factory
            ->state(['nested' => ['overridden_by_state' => true]])
            ->raw(['nested' => ['overridden_by_attributes' => true]]);

        $this->assertSame(
            [
                'from_definition' => true,
                'overridden_by_state' => true,
                'overridden_by_attributes' => true,
            ],
            $result['nested'],
        );
    }

    #[Test]
    public function itMergesMapsRecursivelyButReplacesLists(): void
    {
        $factory = new readonly class extends ImmutableFactory {
            protected function instance(array $attributes): object
            {
                return (object) $attributes;
            }

            public function definition(): array
            {
                return [
                    'list' => ['default'],
                    'map' => [
                        'first' => 'First value',
                        'last' => 'Last value',
                    ],
                ];
            }
        };

        $raw = $factory
            ->state(['list' => []])
            ->raw(['map' => [
                'last' => 'Updated value',
                'added' => 'Added value',
            ]]);

        $this->assertSame(
            [
                'list' => [],
                'map' => [
                    'first' => 'First value',
                    'last' => 'Updated value',
                    'added' => 'Added value',
                ],
            ],
            $raw,
        );
    }

    public static function nestedFactories(): iterable
    {
        yield [
            static fn(ImmutableFactory $instance): ImmutableFactory => $instance->state([
                'nested' => $instance->state([
                    'deeplyNested' => $instance->state(['resolved' => true]),
                ]),
                'nestedArray' => $instance->times(2)->state(['resolvedTimes' => true]),
            ]),
            static function (stdClass $result): void {
                self::assertObjectHasProperty('nested', $result);
                self::assertInstanceOf(stdClass::class, $result->nested);
                self::assertObjectHasProperty('deeplyNested', $result->nested);
                self::assertInstanceOf(stdClass::class, $result->nested->deeplyNested);
                self::assertObjectHasProperty('resolved', $result->nested->deeplyNested);
                self::assertObjectHasProperty('nestedArray', $result);
                self::assertContainsOnlyInstancesOf(stdClass::class, $result->nestedArray);
                self::assertCount(2, $result->nestedArray);
                collect($result->nestedArray)->each(static function (stdClass $item): void {
                    self::assertObjectHasProperty('resolvedTimes', $item);
                    self::assertTrue($item->resolvedTimes);
                });
            },
        ];
    }

    #[Test]
    #[DataProvider('nestedFactories')]
    public function itCanReturnRawAttributesWithNestedFactories(
        /** @param callable(ImmutableFactory): ImmutableFactory $resolveInstance */
        callable $resolveInstance,
        /** @param callable(stdClass): void $assert */
        callable $assert,
    ): void {
        $instance = $resolveInstance(new FakeImmutableFactory());

        $result = $instance->raw();

        $assert((object) $result);
    }

    #[Test]
    #[DataProvider('nestedFactories')]
    public function itCanReturnManyRawAttributesWithNestedFactories(
        /** @param callable(ImmutableFactory): ImmutableFactory $resolveInstance */
        callable $resolveInstance,
        /** @param callable(stdClass): void $assert */
        callable $assert,
    ): void {
        $instance = $resolveInstance(new FakeImmutableFactory());

        $results = $instance->rawMany();

        collect($results)->each(static function (array $result) use ($assert): void {
            $assert((object) $result);
        });
    }

    #[Test]
    #[DataProvider('nestedFactories')]
    public function itCanReturnRawAttributesCollectionsWithNestedFactories(
        /** @param callable(ImmutableFactory): ImmutableFactory $resolveInstance */
        callable $resolveInstance,
        /** @param callable(stdClass): void $assert */
        callable $assert,
    ): void {
        $instance = $resolveInstance(new FakeImmutableFactory());

        $results = $instance->rawCollection();

        $results->each(static function (array $result) use ($assert): void {
            $assert((object) $result);
        });
    }

    #[Test]
    #[DataProvider('nestedFactories')]
    public function itCanMakeOneWithNestedFactories(
        /** @param callable(ImmutableFactory): ImmutableFactory $resolveInstance */
        callable $resolveInstance,
        /** @param callable(stdClass): void $assert */
        callable $assert,
    ): void {
        $instance = $resolveInstance(new FakeImmutableFactory());

        $result = $instance->makeOne();

        $assert($result);
    }

    #[Test]
    #[DataProvider('nestedFactories')]
    public function itCanMakeManyWithNestedFactories(
        /** @param callable(ImmutableFactory): ImmutableFactory $resolveInstance */
        callable $resolveInstance,
        /** @param callable(stdClass): void $assert */
        callable $assert,
    ): void {
        $instance = $resolveInstance(new FakeImmutableFactory());

        $results = $instance->makeMany();

        collect($results)->each($assert(...));
    }

    #[Test]
    #[DataProvider('nestedFactories')]
    public function itCanMakeCollectionsWithNestedFactories(
        /** @param callable(ImmutableFactory): ImmutableFactory $resolveInstance */
        callable $resolveInstance,
        /** @param callable(stdClass, int): mixed $assert */
        callable $assert,
    ): void {
        $instance = $resolveInstance(new FakeImmutableFactory());

        $results = $instance->makeCollection();

        $results->each($assert(...));
    }

    private function assertFactoryProperties(
        ImmutableFactory $factory,
        array $state,
        int $times,
        ?Generator $faker = null,
    ): void {
        $this->when($faker !== null, function () use ($factory, $faker): void {
            $this->assertSame($faker, $factory->faker);
        });
        $this->assertSame($state, $factory->state);
        $this->assertSame($times, $factory->count);
    }

    private function assertHasPropertyForEachDefinition(stdClass $result): void
    {
        foreach (array_keys(new FakeImmutableFactory()->definition()) as $attribute) {
            self::assertObjectHasProperty($attribute, $result);
        }
    }

    private function assertHasArrayKeyForEachDefinition(array $result): void
    {
        foreach (array_keys(new FakeImmutableFactory()->definition()) as $attribute) {
            self::assertArrayHasKey($attribute, $result);
        }
    }
}
