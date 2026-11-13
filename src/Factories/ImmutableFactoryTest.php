<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Factories;

use Craftzing\TestBench\Faker\Providers\Cycle;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

use function array_filter;
use function array_keys;
use function collect;
use function count;

final class ImmutableFactoryTest extends TestCase
{
    #[Test]
    public function itConstructsWithDefaults(): void
    {
        $instance = new FakeImmutableFactory();

        $this->assertInstanceOf(Generator::class, $instance->faker);
        $this->assertSame([], $instance->state);
    }

    #[Test]
    public function itEnablesCycleProviderOnDefaultFakerInstances(): void
    {
        $instance = new FakeImmutableFactory();

        $cycleProviders = array_filter(
            $instance->faker->getProviders(),
            static fn($provider) => $provider instanceof Cycle,
        );

        $this->assertCount(1, $cycleProviders);
        $this->assertSame(1, $instance->faker->cycle([1, 2]));
    }

    #[Test]
    public function itEnablesCycleProviderOnGivenFakerInstances(): void
    {
        $faker = Factory::create();
        $instance = new FakeImmutableFactory($faker);

        $cycleProviders = array_filter(
            $instance->faker->getProviders(),
            static fn($provider) => $provider instanceof Cycle,
        );

        $this->assertCount(1, $cycleProviders);
        $this->assertSame(1, $instance->faker->cycle([1, 2]));
    }

    #[Test]
    public function itDoesNotEnableCycleProviderOnGivenFakerInstancesWhenAlreadyEnabled(): void
    {
        $faker = Factory::create();
        $faker->addProvider(new Cycle($faker));
        $instance = new FakeImmutableFactory($faker);

        $cycleProviders = array_filter(
            $instance->faker->getProviders(),
            static fn($provider) => $provider instanceof Cycle,
        );

        $this->assertCount(1, $cycleProviders);
        $this->assertSame(1, $instance->faker->cycle([1, 2]));
    }

    #[Test]
    public function itAcceptsTimes(): void
    {
        $instance = new FakeImmutableFactory();
        $count = 10;

        $result = $instance->times($count);

        $this->assertSame($instance, $result->factory);
        $this->assertSame($count, $result->count);
    }

    #[Test]
    public function itAcceptsState(): void
    {
        $originalInstance = new FakeImmutableFactory();
        $state = ['state' => true];

        $instance = $originalInstance->state($state);

        $this->assertSame($state, $instance->state);
        $this->assertEmpty($originalInstance->state);
        $this->assertSame($originalInstance->faker, $instance->faker);
    }

    #[Test]
    public function rawUsesDefinitions(): void
    {
        $instance = new readonly class extends ImmutableFactory {
            protected function instance(array $attributes): object
            {
                return (object) $attributes;
            }

            public function definition(): array
            {
                return ['attribute' => 'Defined'];
            }
        };


        $result = $instance->raw();

        $this->assertSame(['attribute' => 'Defined'], $result);
    }

    #[Test]
    public function rawPrioritisesStatesOverDefinitions(): void
    {
        $instance = new readonly class extends ImmutableFactory {
            protected function instance(array $attributes): object
            {
                return (object) $attributes;
            }

            public function definition(): array
            {
                return ['attribute' => 'Defined'];
            }
        };

        $result = $instance->state(['attribute' => 'State'])->raw();

        $this->assertSame(['attribute' => 'State'], $result);
    }

    #[Test]
    public function rawPrioritisesAttributesOverStates(): void
    {
        $instance = new FakeImmutableFactory()->state(['attribute' => 'State']);

        $result = $instance->raw(['attribute' => 'Raw']);

        $this->assertSame(['attribute' => 'Raw'], $result);
    }

    #[Test]
    public function makeOneUsesDefinitions(): void
    {
        $instance = new readonly class extends ImmutableFactory {
            protected function instance(array $attributes): object
            {
                return (object) $attributes;
            }

            public function definition(): array
            {
                return ['attribute' => 'Defined'];
            }
        };


        $result = $instance->makeOne();

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals((object) ['attribute' => 'Defined'], $result);
    }

    #[Test]
    public function makeOnePrioritisesStatesOverDefinitions(): void
    {
        $instance = new readonly class extends ImmutableFactory {
            protected function instance(array $attributes): object
            {
                return (object) $attributes;
            }

            public function definition(): array
            {
                return ['attribute' => 'Defined'];
            }
        };


        $result = $instance->state(['attribute' => 'State'])->makeOne();

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals((object) ['attribute' => 'State'], $result);
    }

    #[Test]
    public function makeOnePrioritisesAttributesOverStates(): void
    {
        $instance = new FakeImmutableFactory()->state(['last' => 'State']);

        $result = $instance->makeOne(['last' => 'Attribute']);

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertHasPropertyForEachDefinition($result);
        $this->assertSame('Attribute', $result->last);
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

    public static function nestedClosures(): iterable
    {
        yield 'Nested' => [
            ['nested' => fn () => 'Deferred #1'],
            ['nested' => 'Deferred #1'],
        ];

        yield 'Deeply nested' => [
            [
                'deeply' => [
                    'nested' => fn () => 'Deferred',
                ],
            ],
            [
                'deeply' => ['nested' => 'Deferred'],
            ],
        ];

        yield 'Closure returning another Closure' => [
            [
                'nested' => fn() => (
                    fn() => 'Deferred'
                ),
            ],
            ['nested' => 'Deferred'],
        ];
    }

    #[Test]
    #[DataProvider('nestedClosures')]
    public function rawResolvesNestedClosuresInStates(array $attributes, array $expected): void
    {
        $instance = new FakeImmutableFactory()->state($attributes);

        $result = $instance->raw();

        $this->assertEquals($expected, $result);
    }

    #[Test]
    #[DataProvider('nestedClosures')]
    public function rawResolvesNestedClosuresInAttributes(array $attributes, array $expected): void
    {
        $instance = new FakeImmutableFactory();

        $result = $instance->raw($attributes);

        $this->assertSame($expected, $result);
    }

    #[Test]
    #[DataProvider('nestedClosures')]
    public function makeOneResolvesNestedClosuresInStates(array $attributes, array $expected): void
    {
        $instance = new FakeImmutableFactory()->state($attributes);

        $result = $instance->makeOne();

        $this->assertEquals((object) $expected, $result);
    }

    #[Test]
    #[DataProvider('nestedClosures')]
    public function makeOneResolvesNestedClosuresInAttributes(array $attributes, array $expected): void
    {
        $instance = new FakeImmutableFactory();

        $result = $instance->makeOne($attributes);

        $this->assertEquals((object) $expected, $result);
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
