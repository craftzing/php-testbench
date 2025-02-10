<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Concerns;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\Concerns\InteractsWithTime;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;

/**
 * @mixin TestCase
 */
trait FreezesTime
{
    use InteractsWithTime;

    private CarbonImmutable $now;

    #[Before]
    public function freezeAtPointInTime(): void
    {
        if (! $this instanceof BaseTestCase) {
            $this->travelTo($this->now = CarbonImmutable::now()->startOfSecond());

            return;
        }

        $this->afterApplicationCreated(function (): void {
            $this->travelTo($this->now = CarbonImmutable::now()->startOfSecond());
        });
    }

    #[After]
    public function unfreezeTime(): void
    {
        unset($this->now);

        $this->travelBack();
    }

    private function travelToNow(): void
    {
        $this->travelTo($this->now);
    }

    private function assertNow(DateTimeImmutable $instance): void
    {
        $this->assertEquals($this->now, $instance);
    }
}