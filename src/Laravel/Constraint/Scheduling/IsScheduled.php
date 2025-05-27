<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Scheduling;

use Cron\CronExpression;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Constraint\Constraint;
use UnexpectedValueException;

use function collect;
use function is_string;

final class IsScheduled extends Constraint
{
    /**
     * @var Collection<int, Event>
     */
    private Collection $events;

    private ?Event $matchingScheduledTask = null;

    public function __construct(
        private readonly string $frequency,
        private readonly Container $app,
    ) {
        // The Artisan console must be initialised in order to schedule the tasks...
        new Artisan($this->app, $this->app['events'], 'testing');

        /** @var Schedule $schedule */
        $schedule = $this->app[Schedule::class];
        $this->events = collect($schedule->events());
    }

    protected function matches(mixed $other): bool
    {
        if (is_string($other) === false) {
            throw new UnexpectedValueException('Scheduling assertions must be using on classnames of scheduled tasks.');
        }

        $this->matchingScheduledTask = $this->events->first(fn (Event $event): bool => $event->description === $other);

        if ($this->matchingScheduledTask === null) {
            return false;
        }

        $expectedExpression = new CronExpression($this->frequency);
        $actualExpression = new CronExpression($this->matchingScheduledTask->expression);

        if ($expectedExpression->getExpression() !== $actualExpression->getExpression()) {
            return false;
        }

        return true;
    }

    protected function failureDescription(mixed $other): string
    {
        $message = "task [$other] {$this->toString()}";

        if ($this->matchingScheduledTask === null) {
            return $message;
        }

        return "$message $this->frequency as it is scheduled to run {$this->matchingScheduledTask->expression}";
    }

    public function toString(): string
    {
        return 'is scheduled';
    }
}
