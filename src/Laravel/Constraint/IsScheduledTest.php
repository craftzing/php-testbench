<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint;

use Illuminate\Cache\CacheManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\TestCase;

final class IsScheduledTest extends TestCase
{
    private function app(): Application
    {
        $app = new Application();
        $app->bind(Factory::class, fn (): Factory => new CacheManager($app));
        $app->singleton(Schedule::class, Schedule::class);

        return $app;
    }

    #[Test]
    public function itExtendsConstrained(): void
    {
        $this->assertInstanceOf(Constraint::class, new IsScheduled('@hourly', $this->app()));
    }

    #[Test]
    public function itCanVerifyACommandIsScheduled(): void
    {
        $app = $this->app();
        $schedule = $app->make(Schedule::class);
        $schedule->job(FakeCommand::class)->cron('@hourly');

        $this->assertThat(FakeCommand::class, new IsScheduled('@hourly', $app));
    }
}

final class FakeCommand
{
    //
}
