<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Concerns;

use Carbon\CarbonImmutable;
use Craftzing\TestBench\Laravel\TestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class FreezesTimeTest extends TestCase
{
    use FreezesTime;

    #[Test]
    public function itCanFreezeTime(): void
    {
        $this->travelTo($future = CarbonImmutable::now()->addMinutes(10));
        $this->assertNotEquals($future, $this->now);
    }

    public static function pointsInTime(): Generator
    {
        yield 'past' => [CarbonImmutable::now()->subMinutes(10)];
        yield 'future' => [CarbonImmutable::now()->addMinutes(10)];
    }

    #[Test]
    #[DataProvider('pointsInTime')]
    public function itCanTravelToNow(CarbonImmutable $pointInTime): void
    {
        $this->travelTo($pointInTime);
        $this->assertEquals(CarbonImmutable::now(), $pointInTime);

        $this->travelToNow();

        $this->assertEquals(CarbonImmutable::now(), $this->now);
    }

    #[Test]
    public function itCanAssertNow(): void
    {
        $this->assertNow(CarbonImmutable::now());
    }
}
