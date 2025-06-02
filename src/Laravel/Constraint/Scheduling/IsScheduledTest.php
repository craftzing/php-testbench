<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Scheduling;

use Illuminate\Console\Scheduling\Schedule;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use stdClass;

/**
 * @codeCoverageIgnore
 */
final class IsScheduledTest extends TestCase
{
    private Schedule $schedule {
        get => $this->app->make(Schedule::class);
    }

    #[Test]
    public function itCanConstruct(): void
    {
        $instance = new IsScheduled('@hourly', $this->app);

        $this->assertInstanceOf(Constraint::class, $instance);
    }

    #[Test]
    #[TestWith([true], 'Boolean')]
    #[TestWith([1], 'Integers')]
    #[TestWith([['ScheduledTask']], 'Array')]
    #[TestWith(['NotAClass'], 'Not a class')]
    public function itCannotEvaluateUnsupportedValueTypes(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(IsScheduled::class . ' can only be evaluated for classnames of scheduled tasks.');

        $this->assertThat($value, new IsScheduled('@hourly', $this->app));
    }

    #[Test]
    public function itFailsWhenNotScheduled(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('is scheduled @hourly.');
        $this->expectExceptionMessage('Not scheduled.');

        $this->assertThat(stdClass::class, new IsScheduled('@hourly', $this->app));
    }

    #[Test]
    public function itFailsWhenNotScheduledWithGivenFrequency(): void
    {
        $this->schedule->job(stdClass::class)->cron('@monthly');

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('is scheduled @hourly.');
        $this->expectExceptionMessage('Scheduled to run 0 0 1 * *.');

        $this->assertThat(stdClass::class, new IsScheduled('@hourly', $this->app));
    }

    #[Test]
    public function itPassesWhenScheduled(): void
    {
        $this->schedule->job(stdClass::class)->cron('@hourly');

        $this->assertThat(stdClass::class, new IsScheduled('@hourly', $this->app));
    }
}
