<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Factories;

use BadMethodCallException;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

use function array_pad;
use function compact;
use function is_callable;
use function random_int;
use function value;

final class ContainerFactoryTest extends TestCase
{
    #[Test]
    public function itCannotConstructWhenConfiguredToCreateContainersWithLessThanOneInstance(): void
    {
        $factory = self::createStub(ImmutableFactory::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('at least one instance');

        new ContainerFactory($factory, 0);
    }

    #[Test]
    #[TestWith([1])]
    #[TestWith([10])]
    #[TestWith([PHP_INT_MAX])]
    public function itConstructsWhenConfiguredToCreateContainersWithAtLeastOneInstance(int $count): void
    {
        $factory = self::createStub(ImmutableFactory::class);

        $instance = new ContainerFactory($factory, $count);

        $this->assertSame($factory, $instance->factory);
        $this->assertSame($count, $instance->count);
    }

    public static function instanceMethodMixins(): iterable
    {
        yield 'definition' => [
            'definition',
            ['first' => 'Definition'],
            [],
        ];

        yield 'raw' => [
            'raw',
            ['first' => 'Raw'],
            [],
        ];

        yield 'raw with attributes' => [
            'raw',
            static fn(array $attributes): array => $attributes,
            ['last' => 'Attribute'],
        ];

        yield 'makeOne' => [
            'makeOne',
            (object) ['first' => 'Make'],
            [],
        ];

        yield 'makeOne with attributes' => [
            'makeOne',
            static fn(array $attributes): object => (object) $attributes,
            ['last' => 'Attribute'],
        ];
    }

    #[Test]
    #[DataProvider('instanceMethodMixins')]
    public function itAcceptsInstanceMethodsFromImmutableFactoryAsMixins(string $method, mixed $methodResult, array $attributes): void
    {
        $factory = self::createDynamicStub(ImmutableFactory::class, [$method => $methodResult]);
        $instance = new ContainerFactory($factory, 6);

        $result = $instance->{$method}($attributes);

        $this->assertEquals(value($methodResult, $attributes), $result);
    }

    #[Test]
    public function itAcceptsTimesModifiersFromImmutableFactoryAsMixin(): void
    {
        $factory = new FakeImmutableFactory();
        $instance = new ContainerFactory($factory, 6);

        $result = $instance->times(3);

        $this->assertInstanceOf(ContainerFactory::class, $result);
        $this->assertSame(3, $result->count);
        $this->assertSame($factory, $result->factory);
        $this->assertNotEquals($instance, $result);
        $this->assertSame(6, $instance->count);
    }

    #[Test]
    public function itAcceptsStateModifiersFromImmutableFactoryAsMixin(): void
    {
        $state = ['first' => 'State'];
        $count = 6;
        $factory = new FakeImmutableFactory();
        $instance = new ContainerFactory($factory, $count);

        $result = $instance->state($state);

        $this->assertInstanceOf(ContainerFactory::class, $result);
        $this->assertSame($state, $result->factory->state);
        $this->assertSame($count, $result->count);
        $this->assertNotEquals($instance, $result);
        $this->assertEmpty($instance->factory->state);
    }

    #[Test]
    #[TestWith(['instance'], 'Protected')]
    #[TestWith(['resolveValue'], 'Private')]
    #[TestWith(['nonExisting'], 'Non-existing')]
    public function itDoesNotAcceptInaccessibleMethodsOnImmutableFactoryAsMixins(string $method): void
    {
        $factory = self::createStub(ImmutableFactory::class);
        $instance = new ContainerFactory($factory, 3);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessageIsOrContains($method);
        $this->expectExceptionMessageIsOrContains($factory::class);

        $instance->{$method}();
    }

    #[Test]
    public function rawManyUsesRawFromFactory(): void
    {
        $count = random_int(1, 10);
        $raw = ['first' => 'Raw'];
        $factory = self::createConfiguredStub(ImmutableFactory::class, compact('raw'));
        $instance = new ContainerFactory($factory, $count);

        $results = $instance->rawMany();

        $this->assertSame(array_pad([], $count, $raw), $results);
    }

    #[Test]
    public function rawManyPassesAttributesToRawFromFactory(): void
    {
        $count = random_int(1, 10);
        $factory = self::createDynamicStub(ImmutableFactory::class, [
            'raw' => static fn(array $attributes): array => $attributes,
        ]);
        $instance = new ContainerFactory($factory, $count);
        $attributes = ['first' => 'Attribute'];

        $results = $instance->rawMany($attributes);

        $this->assertSame(array_pad([], $count, $attributes), $results);
    }

    #[Test]
    public function rawCollectionUsesRawFromFactory(): void
    {
        $count = random_int(1, 10);
        $raw = ['first' => 'Raw'];
        $factory = self::createConfiguredStub(ImmutableFactory::class, compact('raw'));
        $instance = new ContainerFactory($factory, $count);

        $results = $instance->rawCollection();

        $this->assertEquals(Collection::times($count, static fn(): array => $raw), $results);
    }

    #[Test]
    public function rawCollectionPassesAttributesToRawFromFactory(): void
    {
        $count = random_int(1, 10);
        $attributes = ['first' => 'Raw'];
        $factory = self::createDynamicStub(ImmutableFactory::class, [
            'raw' => static fn(array $attributes): array => $attributes,
        ]);
        $instance = new ContainerFactory($factory, $count);

        $results = $instance->rawCollection($attributes);

        $this->assertEquals(Collection::times($count, static fn(): array => $attributes), $results);
    }

    #[Test]
    public function makeManyUsesMakeOneFromFactory(): void
    {
        $count = random_int(1, 10);
        $makeOne = (object) ['first' => 'MakeOne'];
        $factory = self::createConfiguredStub(ImmutableFactory::class, compact('makeOne'));
        $instance = new ContainerFactory($factory, $count);

        $results = $instance->makeMany();

        $this->assertSame(array_pad([], $count, $makeOne), $results);
    }

    #[Test]
    public function makeManyPassesAttributesToMakeOneFromFactory(): void
    {
        $count = random_int(1, 10);
        $factory = self::createDynamicStub(ImmutableFactory::class, [
            'makeOne' => static fn(array $attributes): object => (object) $attributes,
        ]);
        $instance = new ContainerFactory($factory, $count);
        $attributes = ['first' => 'Attribute'];

        $results = $instance->makeMany($attributes);

        $this->assertEquals(array_pad([], $count, (object) $attributes), $results);
    }

    #[Test]
    public function makeCollectionUsesMakeFromFactory(): void
    {
        $count = random_int(1, 10);
        $makeOne = (object) ['first' => 'Raw'];
        $factory = self::createConfiguredStub(ImmutableFactory::class, compact('makeOne'));
        $instance = new ContainerFactory($factory, $count);

        $results = $instance->makeCollection();

        $this->assertEquals(Collection::times($count, static fn(): object => $makeOne), $results);
    }

    #[Test]
    public function makeCollectionPassesAttributesToMakeOneFromFactory(): void
    {
        $count = random_int(1, 10);
        $factory = self::createDynamicStub(ImmutableFactory::class, [
            'makeOne' => static fn(array $attributes): object => (object) $attributes,
        ]);
        $instance = new ContainerFactory($factory, $count);
        $attributes = ['first' => 'Attribute'];

        $results = $instance->makeCollection($attributes);

        $this->assertEquals(Collection::times($count, static fn(): object => (object) $attributes), $results);
    }

    /**
     * @template StubType of object
     * @param class-string<StubType> $classFQN
     * @param array<string, callable> $dynamicConfiguration
     * @return StubType&Stub
     */
    private static function createDynamicStub(string $classFQN, array $dynamicConfiguration): Stub
    {
        $stub = self::createStub($classFQN);

        foreach ($dynamicConfiguration as $attribute => $value) {
            if (is_callable($value)) {
                $stub->method($attribute)->willReturnCallback($value);
                continue;
            }

            $stub->method($attribute)->willReturn($value);
        }

        return $stub;
    }
}
