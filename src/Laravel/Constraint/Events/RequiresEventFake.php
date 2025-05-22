<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Events;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Testing\Fakes\EventFake;
use LogicException;

trait RequiresEventFake
{
    private readonly EventFake $eventFake;

    /**
     * @param class-string ...$eventsToSpyOn
     */
    public static function spy(string ...$eventsToSpyOn): void
    {
        Event::fake($eventsToSpyOn);
    }

    private function resolveEventFake(): EventFake
    {
        Event::isFake() or throw new LogicException(
            'To use the ' . self::class . ' constraint, make sure to call ' . self::class . '::spy() first.',
        );

        return Event::getFacadeRoot();
    }
}
