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
        if (Event::isFake() === false) {
            throw new LogicException(
                'To use the ' . self::class . ' constraint, make sure to call ' . self::class . '::spy() first.',
            );
        }

        // @mago-expect analyzer:mixed-return-statement
        return Event::getFacadeRoot();
    }
}
