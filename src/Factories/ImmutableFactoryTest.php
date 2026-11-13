<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Factories;

use Craftzing\TestBench\Faker\Providers\Cycle;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

use function array_filter;
use function value;

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
        $attributes = ['last' => 'Attribute'];
        $instance = new FakeImmutableFactory()->state(['last' => 'State']);

        $result = $instance->makeOne($attributes);

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals((object) $attributes, $result);
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

        $this->assertSame($expected, $result);
    }

    #[Test]
    #[DataProvider('state')]
    public function itCanMakeOneWithState(array $attributes, array $state, array $expected): void
    {
        $result = new FakeImmutableFactory()
            ->state($state)
            ->makeOne($attributes);

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals((object) $expected, $result);
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

    public static function deferredAttributes(): iterable
    {
        $closureResult = 'Deferred Closure';
        $factoryAttributes = ['factory' => 'Deferred Factory'];

        yield 'Nested DeferredAttribute instance' => [
            ['nested' => self::fakeDeferredAttribute($closureResult)],
            ['nested' => $closureResult],
        ];

        yield 'Nested ImmutableFactory instances' => [
            ['nested' => new FakeImmutableFactory()->state($factoryAttributes)],
            ['nested' => (object) $factoryAttributes],
        ];

        yield 'Nested ContainerFactory instances' => [
            [
                'nested' => new FakeImmutableFactory()
                    ->state($factoryAttributes)
                    ->times(2),
            ],
            [
                'nested' => [
                    (object) $factoryAttributes,
                    (object) $factoryAttributes,
                ],
            ],
        ];

        yield 'Deeply nested DeferredAttribute instance' => [
            [
                'deeply' => [
                    'nested' => self::fakeDeferredAttribute($closureResult),
                ],
            ],
            [
                'deeply' => ['nested' => $closureResult],
            ],
        ];

        yield 'Deeply nested ImmutableFactory instances' => [
            [
                'deeply' => [
                    'nested' => new FakeImmutableFactory()->state($factoryAttributes),
                ],
            ],
            [
                'deeply' => [
                    'nested' => (object) $factoryAttributes,
                ],
            ],
        ];

        yield 'Deeply nested ContainerFactory instances' => [
            [
                'deeply' => [
                    'nested' => new FakeImmutableFactory()
                        ->state($factoryAttributes)
                        ->times(2),
                ],
            ],
            [
                'deeply' => [
                    'nested' => [
                        (object) $factoryAttributes,
                        (object) $factoryAttributes,
                    ],
                ],
            ],
        ];

        yield 'DeferredAttribute instances returning another DeferredAttribute instance' => [
            [
                'nested' => self::fakeDeferredAttribute(
                    self::fakeDeferredAttribute($closureResult),
                ),
            ],
            ['nested' => $closureResult],
        ];

        yield 'DeferredAttribute returning ImmutableFactory instance' => [
            ['nested' => self::fakeDeferredAttribute(new FakeImmutableFactory()->state($factoryAttributes))],
            ['nested' => (object) $factoryAttributes],
        ];

        yield 'DeferredAttribute returning ContainerFactory instance' => [
            [
                'nested' => self::fakeDeferredAttribute(
                    new FakeImmutableFactory()
                        ->state($factoryAttributes)
                        ->times(2),
                ),
            ],
            [
                'nested' => [
                    (object) $factoryAttributes,
                    (object) $factoryAttributes,
                ],
            ],
        ];

        yield 'Nested array of deferred attributes' => [
            [
                'nested' => [
                    self::fakeDeferredAttribute($closureResult),
                    new FakeImmutableFactory()->state($factoryAttributes),
                    new FakeImmutableFactory()
                        ->state(['containerFactory' => 'Deferred ContainerFactory'])
                        ->times(2),
                ],
            ],
            [
                'nested' => [
                    $closureResult,
                    (object) $factoryAttributes,
                    [
                        (object) ['containerFactory' => 'Deferred ContainerFactory'],
                        (object) ['containerFactory' => 'Deferred ContainerFactory'],
                    ],
                ],
            ],
        ];

        yield 'Nested collection of deferred attributes' => [
            [
                'nested' => new Collection([
                    self::fakeDeferredAttribute($closureResult),
                    new FakeImmutableFactory()->state($factoryAttributes),
                    new FakeImmutableFactory()
                        ->state(['containerFactory' => 'Deferred ContainerFactory'])
                        ->times(2),
                ]),
            ],
            [
                'nested' => [
                    $closureResult,
                    (object) $factoryAttributes,
                    [
                        (object) ['containerFactory' => 'Deferred ContainerFactory'],
                        (object) ['containerFactory' => 'Deferred ContainerFactory'],
                    ],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('deferredAttributes')]
    public function rawResolvesDeferredAttributesInStates(array $attributes, array $expected): void
    {
        $instance = new FakeImmutableFactory()->state($attributes);

        $result = $instance->raw();

        $this->assertEquals($expected, $result);
    }

    #[Test]
    #[DataProvider('deferredAttributes')]
    public function rawResolvesDeferredAttributesInAttributes(array $attributes, array $expected): void
    {
        $instance = new FakeImmutableFactory();

        $result = $instance->raw($attributes);

        $this->assertEquals($expected, $result);
    }

    #[Test]
    #[DataProvider('deferredAttributes')]
    public function makeOneResolvesDeferredAttributesInStates(array $attributes, array $expected): void
    {
        $instance = new FakeImmutableFactory()->state($attributes);

        $result = $instance->makeOne();

        $this->assertEquals((object) $expected, $result);
    }

    #[Test]
    #[DataProvider('deferredAttributes')]
    public function makeOneResolvesDeferredAttributesInAttributes(array $attributes, array $expected): void
    {
        $instance = new FakeImmutableFactory();

        $result = $instance->makeOne($attributes);

        $this->assertEquals((object) $expected, $result);
    }

    #[Test]
    public function nestedClosuresShouldAcceptImmutableFactoryInstancesAsArgument(): void
    {
        $attributes = [
            'nested' => self::fakeDeferredAttribute(
                static fn(ImmutableFactory $factory) => $factory->faker->randomElement(['Random']),
            ),
        ];
        $instance = new FakeImmutableFactory();

        $results = [
            $instance->raw($attributes),
            $instance->state($attributes)->raw(),
            $instance->makeOne($attributes),
            $instance->state($attributes)->makeOne(),
        ];

        $this->assertEquals(
            [
                ['nested' => 'Random'],
                ['nested' => 'Random'],
                (object) ['nested' => 'Random'],
                (object) ['nested' => 'Random'],
            ],
            $results,
        );
    }

    private static function fakeDeferredAttribute(mixed $result): DeferredAttribute
    {
        return new readonly class($result) implements DeferredAttribute {
            public function __construct(
                private mixed $result,
            ) {}

            public function __invoke(ImmutableFactory $factory): mixed
            {
                return value($this->result, $factory);
            }
        };
    }
}
