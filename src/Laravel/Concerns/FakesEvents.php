<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Concerns;

use Craftzing\TestBench\Laravel\NestedAssertions\IsTrueWhen;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Traits\ReflectsClosures;

use function is_callable;
use function property_exists;

/**
 * @mixin TestCase
 */
trait FakesEvents
{
    use ReflectsClosures;

    protected function fakeEvents(): void
    {
        if (property_exists($this, 'eventsNotToFake')) {
            Event::fakeExcept($this->eventsNotToFake);
        } else {
            Event::fake($this->eventsToFake ?? []);
        }
    }

    /**
     * @param class-string|callable(object): void $event
     */
    private function assertEventWasDispatched(string|callable $event): void
    {
        [$eventType, $callback] = $this->prepareEventTypeAndCallback($event);

        Event::assertDispatched($eventType, $callback);
    }

    /**
     * @param class-string|callable(object): void $event
     */
    private function assertEventWasNotDispatched(string|callable $event): void
    {
        [$eventType, $callback] = $this->prepareEventTypeAndCallback($event);

        Event::assertNotDispatched($eventType, $callback);
    }

    private function assertEventWasDispatchedTimes(string $event, int $times): void
    {
        Event::assertDispatchedTimes($event, $times);
    }

    private function assertNoEventWereDispatched(): void
    {
        Event::assertNothingDispatched();
    }

    public function assertListening(string $event, array|string $expectedListener): void
    {
        Event::assertListening($event, $expectedListener);
    }

    /**
     * @return array<int, class-string|callable>
     */
    private function prepareEventTypeAndCallback(string|callable $event): array
    {
        $eventType = $event;
        $callback = null;

        if (is_callable($event)) {
            $eventType = $this->firstClosureParameterType($event);
            $callback = new IsTrueWhen($event);
        }

        return [$eventType, $callback];
    }
}
