<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Events;

use Craftzing\TestBench\Laravel\Doubles\Events\DummyEvent;
use Craftzing\TestBench\PHPUnit\DataProviders\QuantableConstraintProvider;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use LogicException;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;

final class WasDispatchedTest extends TestCase
{
    #[Test]
    public function itFailsToConstructWhenNotSpying(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(WasDispatched::class . '::spy()');

        new WasDispatched();
    }

    #[Test]
    public function itCanConstructWithEventAssertions(): void
    {
        WasDispatched::spy();
        $assertEvent = function (): void {};

        $instance = new WasDispatched($assertEvent);

        $this->assertSame($assertEvent, $instance->assertEvent);
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraintProvider::class, 'cases')]
    public function itImplementsTheQuantableInterface(QuantableConstraintProvider $quantise): void
    {
        WasDispatched::spy();
        $assertEvent = function (): void {};
        $instance = new WasDispatched($assertEvent);

        $quantisedInstance = $quantise($instance);

        $this->assertNull($instance->times);
        $this->assertSame($assertEvent, $instance->assertEvent);
        $this->assertSame($quantise->times, $quantisedInstance->times);
        $this->assertSame($assertEvent, $quantisedInstance->assertEvent);
    }

    #[Test]
    public function itCannotEvaluateInvalidValuesThatAreNotFCQNs(): void
    {
        WasDispatched::spy();
        $event = new DummyEvent();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(WasDispatched::class . ' can only be evaluated for strings');

        $this->assertThat($event, new WasDispatched());
    }

    #[Test]
    public function itFailsWhenNotDispatched(): void
    {
        WasDispatched::spy();

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('event was dispatched.');

        $this->assertThat('event', new WasDispatched());
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraintProvider::class, 'cases')]
    public function itPassesWhenDispatched(QuantableConstraintProvider $quantise): void
    {
        WasDispatched::spy();
        $event = 'some.event';

        $quantise->applyTo(fn () => Event::dispatch($event));

        $this->assertThat($event, $quantise(new WasDispatched()));
    }

    #[Test]
    public function itFailsWhenNotDispatchedWithExpectedEventAssertions(): void
    {
        WasDispatched::spy();
        Event::dispatch(new DummyEvent(1, 2));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('event was dispatched with expected event assertions.');

        $this->assertThat(DummyEvent::class, new WasDispatched(function (DummyEvent $event): void {
            $this->assertSame(['first', 'last'], $event->arguments);
        }));
    }

    #[Test]
    public function itPassesWhenDispatchedWithExpectedEventAssertions(): void
    {
        WasDispatched::spy();
        $arguments = ['first', 'last'];

        Event::dispatch(new DummyEvent(...$arguments));

        $this->assertThat(DummyEvent::class, new WasDispatched(function (DummyEvent $event) use ($arguments): void {
            $this->assertSame($arguments, $event->arguments);
        }));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraintProvider::class, 'tooFewOrTooManyTimes')]
    public function itFailsWhenNotDispatchedExpectedTimes(QuantableConstraintProvider $quantise): void
    {
        WasDispatched::spy();
        $event = 'some.event';
        $quantise->applyTo(fn () => Event::dispatch($event));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("event was dispatched $quantise->expected times.");

        $this->assertThat($event, new WasDispatched()->times($quantise->expected));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraintProvider::class, 'cases')]
    public function itPassesWhenDispatchedExpectedTimes(QuantableConstraintProvider $quantise): void
    {
        WasDispatched::spy();
        $event = 'some.event';

        $quantise->applyTo(fn () => Event::dispatch($event));

        $this->assertThat($event, $quantise(new WasDispatched()));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraintProvider::class, 'tooFewOrTooManyTimes')]
    public function itFailsWhenNotDispatchedExpectedTimesWithExpectedEventAssertions(
        QuantableConstraintProvider $quantise,
    ): void {
        WasDispatched::spy();
        $arguments = ['first', 'last'];
        $quantise->applyTo(fn () => Event::dispatch(new DummyEvent(...$arguments)));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("event was dispatched $quantise->expected times with expected event assertions.");

        $this->assertThat(DummyEvent::class, new WasDispatched(function (DummyEvent $event) use ($arguments): void {
            $this->assertSame($arguments, $event->arguments);
        })->times($quantise->expected));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraintProvider::class, 'cases')]
    public function itPassesWhenDispatchedExpectedTimesWithExpectedEventAssertions(
        QuantableConstraintProvider $quantise,
    ): void {
        WasDispatched::spy();
        $arguments = ['first', 'last'];

        $quantise->applyTo(fn () => Event::dispatch(new DummyEvent(...$arguments)));

        $this->assertThat(DummyEvent::class, $quantise(new WasDispatched(function (
            DummyEvent $event,
        ) use ($arguments): void {
            $this->assertSame($arguments, $event->arguments);
        })));
    }
}
