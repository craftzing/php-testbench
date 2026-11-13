<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Factories;

use ArgumentCountError;
use Craftzing\TestBench\Faker\Providers\CycleTest as FakerCycleProviderTest;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;
use function class_implements;

final class CycleTest extends TestCase
{
    #[Test]
    public function itImplementsDeferredAttribute(): void
    {
        $this->assertContains(DeferredAttribute::class, class_implements(Cycle::class));
    }

    #[Test]
    #[DataProviderExternal(FakerCycleProviderTest::class, 'lessThanTwoOptions')]
    public function itCannotConstructWithLessThanTwoOptions(array $options): void
    {
        $this->expectException(ArgumentCountError::class);

        new Cycle(...$options);
    }

    #[Test]
    #[DataProviderExternal(FakerCycleProviderTest::class, 'options')]
    public function itCyclesThroughOptions(array $options, array $expected): void
    {
        $factory = new FakeImmutableFactory();
        $instance = new Cycle(...$options);

        $results = array_map(static fn() => $instance($factory), $expected);

        $this->assertSame($expected, $results);
    }

    #[Test]
    public function itCountsOptions(): void
    {
        $instance = new Cycle(0, 1, 2, 3, 4, 5);

        $result = $instance->count();

        $this->assertSame(6, $result);
    }
}
