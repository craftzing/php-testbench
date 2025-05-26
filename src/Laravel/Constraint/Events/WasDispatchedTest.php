<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Events;

use Craftzing\TestBench\Laravel\Doubles\Events\DummyEvent;
use Craftzing\TestBench\PHPUnit\Constraint\Objects\DeriveConstraintsFromObjectUsingFakes;
use Craftzing\TestBench\PHPUnit\Constraint\Objects\DeriveConstraintsFromObjectUsingReflection;
use Craftzing\TestBench\PHPUnit\DataProviders\QuantableConstraintProvider;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use LogicException;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\Constraint\IsIdentical;
use PHPUnit\Framework\ExpectationFailedException;

final class WasDispatchedTest extends TestCase
{
    #[Before]
    public function resetDeriveConstraintsFromObjectUsing(): void
    {
        WasDispatched::deriveConstraintsFromObjectUsing(null);
    }

    #[Test]
    public function itFailsToConstructWhenNotSpying(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(WasDispatched::class . '::spy()');

        new WasDispatched();
    }

    #[Test]
    public function itCanConstructWithoutArguments(): void
    {
        WasDispatched::spy();

        $instance = new WasDispatched();

        $this->assertNull($instance->times);
        $this->assertEmpty($instance->objectConstraints);
    }

    #[Test]
    public function itCanConstructWithEventConstraints(): void
    {
        WasDispatched::spy();
        $eventConstraints = [new IsIdentical('event')];

        $instance = new WasDispatched()->withEventConstraints(...$eventConstraints);

        $this->assertNull($instance->times);
        $this->assertSame($eventConstraints, $instance->objectConstraints);
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraintProvider::class, 'cases')]
    public function itImplementsTheQuantableInterface(QuantableConstraintProvider $quantise): void
    {
        WasDispatched::spy();
        $eventConstraints = [new IsIdentical('event')];
        $instance = new WasDispatched()->withEventConstraints(...$eventConstraints);

        $quantisedInstance = $quantise($instance);

        $this->assertNull($instance->times);
        $this->assertSame($quantise->times, $quantisedInstance->times);
        $this->assertSame($eventConstraints, $instance->objectConstraints);
        $this->assertSame($eventConstraints, $quantisedInstance->objectConstraints);
    }

    #[Test]
    #[TestWith([true], 'Boolean')]
    #[TestWith([1], 'Integers')]
    #[TestWith([['event']], 'Array')]
    public function itCannotEvaluateUnsupportedValueTypes(mixed $value): void
    {
        WasDispatched::spy();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(WasDispatched::class . ' can only be evaluated for strings or event instances');

        $this->assertThat($value, new WasDispatched());
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
    public function itFailsWhenDispatchedButNotWithGivenEventConstraints(): void
    {
        WasDispatched::spy();
        $event = new DummyEvent('first', 'last');
        Event::dispatch($event);

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('event was dispatched with given event constraints.');

        $this->assertThat($event, new WasDispatched()->withEventConstraints(
            new Callback(fn () => false),
        ));
    }

    #[Test]
    public function itPassesWhenDispatchedWithGivenEventConstraints(): void
    {
        WasDispatched::spy();
        $event = new DummyEvent('first', 'last');

        Event::dispatch($event);

        $this->assertThat($event, new WasDispatched()->withEventConstraints(
            new Callback(fn () => true),
        ));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraintProvider::class, 'tooFewOrTooManyTimes')]
    public function itFailsWhenDispatchedButNotGivenTimes(QuantableConstraintProvider $quantise): void
    {
        WasDispatched::spy();
        $event = 'some.event';
        $quantise->applyTo(fn () => Event::dispatch($event));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("event was dispatched $quantise->expected time(s).");

        $this->assertThat($event, new WasDispatched()->times($quantise->expected));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraintProvider::class, 'cases')]
    public function itPassesWhenDispatchedGivenTimes(QuantableConstraintProvider $quantise): void
    {
        WasDispatched::spy();
        $event = 'some.event';

        $quantise->applyTo(fn () => Event::dispatch($event));

        $this->assertThat($event, $quantise(new WasDispatched()));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraintProvider::class, 'tooFewOrTooManyTimes')]
    public function itFailsWhenDispatchedWithGivenEventConstrainsButNotGivenTimes(
        QuantableConstraintProvider $quantise,
    ): void {
        WasDispatched::spy();
        $event = new DummyEvent('first', 'last');
        $quantise->applyTo(fn () => Event::dispatch($event));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("event was dispatched $quantise->expected time(s)");

        $this->assertThat($event, new WasDispatched()->times($quantise->expected)->withEventConstraints(
            new Callback(fn () => true),
        ));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraintProvider::class, 'atLeastOnce')]
    public function itFailsWhenDispatchedGivenTimesButNotWithGivenEventConstrains(
        QuantableConstraintProvider $quantise,
    ): void {
        WasDispatched::spy();
        $event = new DummyEvent('first', 'last');
        $quantise->applyTo(fn () => Event::dispatch($event));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("event was dispatched $quantise->expected time(s) with given event constraints.");

        $this->assertThat($event, new WasDispatched()->times($quantise->expected)->withEventConstraints(
            new Callback(fn () => false),
        ));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraintProvider::class, 'cases')]
    public function itPassesWhenDispatchedGivenTimesWithGivenEventConstraints(
        QuantableConstraintProvider $quantise,
    ): void {
        WasDispatched::spy();
        $event = new DummyEvent('first', 'last');

        $quantise->applyTo(fn () => Event::dispatch($event));

        $this->assertThat($event, $quantise(new WasDispatched()->withEventConstraints(
            new Callback(fn () => true),
        )));
    }

    #[Test]
    public function itCannotDeriveEventConstraintsFromEventStrings(): void
    {
        WasDispatched::spy();

        $eventConstraints = new WasDispatched()->givenOrDerivedObjectConstraints(DummyEvent::class);

        $this->assertEmpty($eventConstraints);
    }

    #[Test]
    public function itCanDeriveEventConstraintsFromEventObjects(): void
    {
        WasDispatched::spy();
        $event = new DummyEvent('actual');
        $expected = new DeriveConstraintsFromObjectUsingReflection()->__invoke($event);

        $eventConstraints = new WasDispatched()->givenOrDerivedObjectConstraints($event);

        $this->assertEquals($expected, $eventConstraints);
    }

    #[Test]
    public function itCanDeriveEventConstraintsFromEventObjectsUsingCustomImplementations(): void
    {
        $event = new DummyEvent('actual');
        $deriveConstraintsFromObject = DeriveConstraintsFromObjectUsingFakes::passingConstraints();
        WasDispatched::spy();
        WasDispatched::deriveConstraintsFromObjectUsing($deriveConstraintsFromObject);

        $eventConstraints = new WasDispatched()->givenOrDerivedObjectConstraints($event);

        $this->assertEquals($deriveConstraintsFromObject->constraints, $eventConstraints);
    }

    #[Test]
    public function itFailsWhenNotDispatchedWithDerivedEventConstraints(): void
    {
        $event = new DummyEvent('actual');
        WasDispatched::spy();
        WasDispatched::deriveConstraintsFromObjectUsing(DeriveConstraintsFromObjectUsingFakes::failingConstraints());
        Event::dispatch($event);

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('event was dispatched with derived event constraints.');

        $this->assertThat($event, new WasDispatched());
    }

    #[Test]
    public function itPassesWhenDispatchedWithDerivedEventConstraints(): void
    {
        $event = new DummyEvent('actual');
        WasDispatched::spy();
        WasDispatched::deriveConstraintsFromObjectUsing(DeriveConstraintsFromObjectUsingFakes::passingConstraints());

        Event::dispatch($event);

        $this->assertThat($event, new WasDispatched());
    }
}
