<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Bus;

use Craftzing\TestBench\PHPUnit\Constraint\Objects\DeriveConstraintsFromObjectUsingFakes;
use Craftzing\TestBench\PHPUnit\Constraint\Objects\DeriveConstraintsFromObjectUsingReflection;
use Craftzing\TestBench\PHPUnit\DataProviders\QuantableConstraint;
use Illuminate\Support\Facades\Bus;
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
use stdClass;

/**
 * @codeCoverageIgnore
 */
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
    public function itCanConstructWithCommandConstraints(): void
    {
        WasDispatched::spy();
        $commandConstraints = [new IsIdentical('command')];

        $instance = new WasDispatched()->withConstraints(...$commandConstraints);

        $this->assertNull($instance->times);
        $this->assertSame($commandConstraints, $instance->objectConstraints);
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraint::class, 'cases')]
    public function itImplementsTheQuantableInterface(QuantableConstraint $quantise): void
    {
        WasDispatched::spy();
        $commandConstraints = [new IsIdentical('command')];
        $instance = new WasDispatched()->withConstraints(...$commandConstraints);

        $quantisedInstance = $quantise($instance);

        $this->assertNull($instance->times);
        $this->assertSame($quantise->times, $quantisedInstance->times);
        $this->assertSame($commandConstraints, $instance->objectConstraints);
        $this->assertSame($commandConstraints, $quantisedInstance->objectConstraints);
    }

    #[Test]
    #[TestWith([true], 'Boolean')]
    #[TestWith([1], 'Integers')]
    #[TestWith([['event']], 'Array')]
    public function itCannotEvaluateUnsupportedValueTypes(mixed $value): void
    {
        WasDispatched::spy();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(WasDispatched::class . ' can only be evaluated for strings or command instances');

        $this->assertThat($value, new WasDispatched());
    }

    #[Test]
    public function itFailsWhenNotDispatched(): void
    {
        WasDispatched::spy();

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('command was dispatched.');

        $this->assertThat(stdClass::class, new WasDispatched());
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraint::class, 'cases')]
    public function itPassesWhenDispatched(QuantableConstraint $quantise): void
    {
        WasDispatched::spy();
        $command = new stdClass();

        $quantise->applyTo(fn () => Bus::dispatch($command));

        $this->assertThat($command::class, $quantise(new WasDispatched()));
    }

    #[Test]
    public function itFailsWhenDispatchedButNotWithGivenCommandConstraints(): void
    {
        WasDispatched::spy();
        $command = new stdClass();
        Bus::dispatch($command);

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('command was dispatched with given command constraints.');

        $this->assertThat($command, new WasDispatched()->withConstraints(
            new Callback(fn () => false),
        ));
    }

    #[Test]
    public function itPassesWhenDispatchedWithGivenCommandConstraints(): void
    {
        WasDispatched::spy();
        $command = new stdClass();

        Bus::dispatch($command);

        $this->assertThat($command, new WasDispatched()->withConstraints(
            new Callback(fn () => true),
        ));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraint::class, 'tooFewOrTooManyTimes')]
    public function itFailsWhenDispatchedButNotGivenTimes(QuantableConstraint $quantise): void
    {
        WasDispatched::spy();
        $command = new stdClass();
        $quantise->applyTo(fn () => Bus::dispatch($command));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("command was dispatched $quantise->expected time(s).");

        $this->assertThat($command::class, new WasDispatched()->times($quantise->expected));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraint::class, 'cases')]
    public function itPassesWhenDispatchedGivenTimes(QuantableConstraint $quantise): void
    {
        WasDispatched::spy();
        $command = new stdClass();

        $quantise->applyTo(fn () => Bus::dispatch($command));

        $this->assertThat($command::class, $quantise(new WasDispatched()));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraint::class, 'tooFewOrTooManyTimes')]
    public function itFailsWhenDispatchedWithGivenCommandConstrainsButNotGivenTimes(
        QuantableConstraint $quantise,
    ): void {
        WasDispatched::spy();
        $command = new stdClass();
        $quantise->applyTo(fn () => Bus::dispatch($command));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("command was dispatched $quantise->expected time(s)");

        $this->assertThat($command, new WasDispatched()->times($quantise->expected)->withConstraints(
            new Callback(fn () => true),
        ));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraint::class, 'atLeastOnce')]
    public function itFailsWhenDispatchedGivenTimesButNotWithGivenCommandConstrains(
        QuantableConstraint $quantise,
    ): void {
        WasDispatched::spy();
        $command = new stdClass();
        $quantise->applyTo(fn () => Bus::dispatch($command));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage(
            "command was dispatched $quantise->expected time(s) with given command constraints.",
        );

        $this->assertThat($command, new WasDispatched()->times($quantise->expected)->withConstraints(
            new Callback(fn () => false),
        ));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraint::class, 'cases')]
    public function itPassesWhenDispatchedGivenTimesWithGivenCommandConstraints(
        QuantableConstraint $quantise,
    ): void {
        WasDispatched::spy();
        $command = new stdClass();

        $quantise->applyTo(fn () => Bus::dispatch($command));

        $this->assertThat($command, $quantise(new WasDispatched()->withConstraints(
            new Callback(fn () => true),
        )));
    }

    #[Test]
    public function itCannotDeriveCommandConstraintsFromCommandStrings(): void
    {
        WasDispatched::spy();
        $command = new readonly class
        {
            public function __construct(
                public string $first = 'first',
            ) {}
        };

        $commandConstraints = new WasDispatched()->givenOrDerivedObjectConstraints($command::class);

        $this->assertEmpty($commandConstraints);
    }

    #[Test]
    public function itCanDeriveCommandConstraintsFromCommandObjects(): void
    {
        WasDispatched::spy();
        $command = new readonly class
        {
            public function __construct(
                public string $first = 'first',
            ) {}
        };
        $expected = new DeriveConstraintsFromObjectUsingReflection()->__invoke($command);

        $commandConstraints = new WasDispatched()->givenOrDerivedObjectConstraints($command);

        $this->assertNotEmpty($commandConstraints);
        $this->assertEquals($expected, $commandConstraints);
    }

    #[Test]
    public function itCanDeriveCommandConstraintsFromCommandObjectsUsingCustomImplementations(): void
    {
        $command = new stdClass();
        $deriveConstraintsFromObject = DeriveConstraintsFromObjectUsingFakes::passingConstraints();
        WasDispatched::spy();
        WasDispatched::deriveConstraintsFromObjectUsing($deriveConstraintsFromObject);

        $commandConstraints = new WasDispatched()->givenOrDerivedObjectConstraints($command);

        $this->assertEquals($deriveConstraintsFromObject->constraints, $commandConstraints);
    }

    #[Test]
    public function itFailsWhenNotDispatchedWithDerivedCommandConstraints(): void
    {
        $command = new stdClass();
        WasDispatched::spy();
        WasDispatched::deriveConstraintsFromObjectUsing(DeriveConstraintsFromObjectUsingFakes::failingConstraints());
        Bus::dispatch($command);

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('command was dispatched with derived command constraints.');

        $this->assertThat($command, new WasDispatched());
    }

    #[Test]
    public function itPassesWhenDispatchedWithDerivedCommandConstraints(): void
    {
        $command = new stdClass();
        WasDispatched::spy();
        WasDispatched::deriveConstraintsFromObjectUsing(DeriveConstraintsFromObjectUsingFakes::passingConstraints());

        Bus::dispatch($command);

        $this->assertThat($command, new WasDispatched());
    }
}
