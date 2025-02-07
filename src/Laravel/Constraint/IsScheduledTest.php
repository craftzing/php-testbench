<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint;

use Craftzing\TestBench\Laravel\TestCase;
use Illuminate\Cache\CacheManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Cache\Factory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Constraint\Constraint;

final class IsScheduledTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(Factory::class, fn (): Factory => new CacheManager($this->app));
        $this->app->singleton(Schedule::class, Schedule::class);
    }

    #[Test]
    public function itExtendsConstrained(): void
    {
        $this->assertInstanceOf(Constraint::class, new IsScheduled('@hourly', $this->app));
    }

    #[Test]
    public function itCanVerifyACommandIsScheduled(): void
    {
        $schedule = $this->app->make(Schedule::class);
        $schedule->job(FakeCommand::class)->cron('@hourly');

        $this->assertThat(FakeCommand::class, new IsScheduled('@hourly', $this->app));
    }
}

final class FakeCommand
{
    //
}
