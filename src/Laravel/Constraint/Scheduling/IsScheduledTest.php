<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Scheduling;

use Illuminate\Console\Scheduling\Schedule;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Constraint\Constraint;
use stdClass;

final class IsScheduledTest extends TestCase
{
    #[Test]
    public function itExtendsConstrained(): void
    {
        $this->assertInstanceOf(Constraint::class, new IsScheduled('@hourly', $this->app));
    }

    #[Test]
    public function itCanVerifyACommandIsScheduled(): void
    {
        $schedule = $this->app->make(Schedule::class);

        $schedule->job(stdClass::class)->cron('@hourly');

        $this->assertThat(stdClass::class, new IsScheduled('@hourly', $this->app));
    }
}
