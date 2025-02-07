<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Concerns;

use AllowDynamicProperties;
use Craftzing\TestBench\Laravel\Concerns\TestFixture\FakeAlternateEvent;
use Craftzing\TestBench\Laravel\Concerns\TestFixture\FakeEvent;
use Craftzing\TestBench\Laravel\TestCase;
use Generator;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Testing\Fakes\EventFake;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

#[AllowDynamicProperties]
final class FakesEventsTest extends TestCase
{
    use FakesEvents;

    protected function setUp(): void
    {
        $this->refreshApplication();

        $events = new Dispatcher();
        $this->app->singleton(DispatcherContract::class, fn (): DispatcherContract => $events);
        $this->app->singleton('cache', fn () => $this->createMock(Repository::class));
        
        Event::setFacadeApplication($this->app);

        parent::setUp();
    }

    #[After]
    public function unsetEventProperties(): void
    {
        unset($this->eventsToFake, $this->eventsNotToFake);
    }

    #[Test]
    public function itCanSwapTheDispatcherForAFake(): void
    {
        $this->fakeEvents();

        $this->assertInstanceOf(EventFake::class, Event::getFacadeRoot());
    }

    #[Test]
    public function itCanAddEventsToFake(): void
    {
        $this->eventsToFake = [
            FakeEvent::class,
        ];
        $this->fakeEvents();

        Event::dispatch(new FakeEvent());
        Event::dispatch(new FakeAlternateEvent());

        Event::assertDispatched(FakeEvent::class);
        Event::assertNotDispatched(FakeAlternateEvent::class);
    }

    #[Test]
    public function itCanExemptEventsToFake(): void
    {
        $this->eventsNotToFake = [
            FakeAlternateEvent::class,
        ];
        $this->fakeEvents();

        Event::dispatch(new FakeEvent());
        Event::dispatch(new FakeAlternateEvent());

        Event::assertDispatched(FakeEvent::class);
        Event::assertNotDispatched(FakeAlternateEvent::class);
    }

    public static function eventAssertions(): Generator
    {
        yield 'class-string' => [FakeEvent::class];
        yield 'callable' => [
            fn (FakeEvent $event): bool => $event->value === 1,
        ];
    }

    #[Test]
    #[DataProvider('eventAssertions')]
    public function itCanAssertEventWasDispatched(string|callable $assertion): void
    {
        $this->fakeEvents();

        Event::dispatch(new FakeEvent(1));

        $this->assertEventWasDispatched($assertion);
    }

    #[Test]
    public function itCanAssertEventWasNotDispatched(): void
    {
        $this->fakeEvents();

        Event::dispatch(new FakeEvent());

        $this->assertEventWasNotDispatched(fn (FakeEvent $event): bool => $event->value === 1);
    }
}