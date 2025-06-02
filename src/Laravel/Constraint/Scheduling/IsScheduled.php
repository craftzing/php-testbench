<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Scheduling;

use Craftzing\TestBench\PHPUnit\Constraint\ProvidesAdditionalFailureDescription;
use Cron\CronExpression;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Constraint\Constraint;

use function is_string;

final class IsScheduled extends Constraint
{
    use ProvidesAdditionalFailureDescription;

    private readonly Schedule $schedule;

    public function __construct(
        private readonly string $frequency,
        Container $container,
    ) {
        // The Artisan console must be initialised in order to schedule the tasks...
        new Artisan($container, $container['events'], 'testing');
        $this->schedule = $container->make(Schedule::class);
    }

    #[Override]
    protected function matches(mixed $other): bool
    {
        is_string($other) && class_exists($other) or throw new InvalidArgumentException(
            self::class . ' can only be evaluated for classnames of scheduled tasks.',
        );

        $matchingScheduledTask = new Collection($this->schedule->events())
            ->first(fn (Event $event): bool => $event->description === $other);

        if ($matchingScheduledTask === null) {
            $this->additionalFailureDescriptions[] = 'Not scheduled.';

            return false;
        }

        $expectedExpression = new CronExpression($this->frequency);
        $actualExpression = new CronExpression($matchingScheduledTask->expression);

        if ($expectedExpression->getExpression() !== $actualExpression->getExpression()) {
            $this->additionalFailureDescriptions[] = "Scheduled to run {$actualExpression->getExpression()}.";

            return false;
        }

        return true;
    }

    public function toString(): string
    {
        return "is scheduled $this->frequency";
    }
}
