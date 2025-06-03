<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Bus;

use Craftzing\TestBench\PHPUnit\Constraint\Objects\DeriveConstraintsFromObjectUsingFakes;
use Craftzing\TestBench\PHPUnit\Constraint\Objects\DeriveConstraintsFromObjectUsingReflection;
use Craftzing\TestBench\PHPUnit\DataProviders\QuantableConstraintProvider;
use Craftzing\TestBench\PHPUnit\Doubles\SpyCallable;
use Illuminate\Support\Facades\Bus;
use InvalidArgumentException;
use LogicException;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\ExpectationFailedException;
use stdClass;

/**
 * @codeCoverageIgnore
 */
final class WasHandledTest extends TestCase
{
    #[Before]
    public function resetDeriveConstraintsFromObjectUsing(): void
    {
        WasHandled::deriveConstraintsFromObjectUsing(null);
    }

    public static function callableHandlers(): iterable
    {
        yield 'Closure' => [fn (stdClass $object): string => 'handled'];
        yield 'Invokable class' => [
            new readonly class
            {
                public function __invoke(stdClass $command): string
                {
                    return 'handled';
                }
            },
        ];
    }

    #[Test]
    #[DataProvider('callableHandlers')]
    public function itCanMapGivenCallablesToHandleCommands(callable $handler): void
    {
        WasHandled::using($handler, $this->app);

        $this->assertEquals(new SpyCallable($handler), Bus::getCommandHandler(new stdClass()));
    }

    #[Test]
    public function itCanConstructWithoutArguments(): void
    {
        $instance = new WasHandled();

        $this->assertNull($instance->times);
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraintProvider::class, 'cases')]
    public function itImplementsTheQuantableInterface(QuantableConstraintProvider $quantise): void
    {
        $instance = new WasHandled();

        $quantisedInstance = $quantise($instance);

        $this->assertNull($instance->times);
        $this->assertSame($quantise->times, $quantisedInstance->times);
    }

    #[Test]
    #[TestWith([true], 'Boolean')]
    #[TestWith([1], 'Integers')]
    #[TestWith([['event']], 'Array')]
    #[TestWith([['NonExistingClass']], 'Non-existing class')]
    public function itCannotEvaluateUnsupportedValueTypes(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(WasHandled::class . ' can only be evaluated for strings or command instances');

        $this->assertThat($value, new WasHandled());
    }

    #[Test]
    public function itFailsWhenNotUsingGivenCallables(): void
    {
        $this->expectException(LogicException::class);

        $this->assertThat(stdClass::class, new WasHandled());
    }

    #[Test]
    public function itFailsWhenNotHandled(): void
    {
        WasHandled::using(fn (stdClass $command): string => 'handled', $this->app);

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('command was handled.');

        $this->assertThat(stdClass::class, new WasHandled());
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraintProvider::class, 'cases')]
    public function itPassesWhenHandled(QuantableConstraintProvider $quantise): void
    {
        WasHandled::using(fn (stdClass $command): string => 'handled', $this->app);
        $command = new stdClass();

        $quantise->applyTo(fn () => Bus::dispatch($command));

        $this->assertThat($command::class, $quantise(new WasHandled()));
    }

    #[Test]
    public function itFailsWhenHandledButNotWithGivenCommandConstraints(): void
    {
        WasHandled::using(fn (stdClass $command): string => 'handled', $this->app);
        $command = new stdClass();
        Bus::dispatch($command);

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('command was handled with given command constraints.');

        $this->assertThat($command::class, new WasHandled()->withCommandConstraints(
            new Callback(fn () => false),
        ));
    }

    #[Test]
    public function itPassesWhenHandledWithGivenCommandConstraints(): void
    {
        WasHandled::using(fn (stdClass $command): string => 'handled', $this->app);
        $command = new stdClass();

        Bus::dispatch($command);

        $this->assertThat($command::class, new WasHandled()->withCommandConstraints(
            new Callback(fn () => true),
        ));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraintProvider::class, 'tooFewOrTooManyTimes')]
    public function itFailsWhenHandledButNotGivenTimes(QuantableConstraintProvider $quantise): void
    {
        WasHandled::using(fn (stdClass $command): string => 'handled', $this->app);
        $command = new stdClass();
        $quantise->applyTo(fn () => Bus::dispatch($command));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("command was handled $quantise->expected time(s).");

        $this->assertThat($command::class, new WasHandled()->times($quantise->expected));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraintProvider::class, 'cases')]
    public function itPassesWhenHandledGivenTimes(QuantableConstraintProvider $quantise): void
    {
        WasHandled::using(fn (stdClass $command): string => 'handled', $this->app);
        $command = new stdClass();

        $quantise->applyTo(fn () => Bus::dispatch($command));

        $this->assertThat($command::class, $quantise(new WasHandled()));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraintProvider::class, 'tooFewOrTooManyTimes')]
    public function itFailsWhenHandledWithGivenCommandConstrainsButNotGivenTimes(
        QuantableConstraintProvider $quantise,
    ): void {
        WasHandled::using(fn (stdClass $command): string => 'handled', $this->app);
        $command = new stdClass();
        $quantise->applyTo(fn () => Bus::dispatch($command));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("command was handled $quantise->expected time(s)");

        $this->assertThat($command, new WasHandled()->times($quantise->expected)->withCommandConstraints(
            new Callback(fn () => true),
        ));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraintProvider::class, 'atLeastOnce')]
    public function itFailsWhenHandledGivenTimesButNotWithGivenCommandConstrains(
        QuantableConstraintProvider $quantise,
    ): void {
        WasHandled::using(fn (stdClass $command): string => 'handled', $this->app);
        $command = new stdClass();
        $quantise->applyTo(fn () => Bus::dispatch($command));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage(
            "command was handled $quantise->expected time(s) with given command constraints.",
        );

        $this->assertThat($command, new WasHandled()->times($quantise->expected)->withCommandConstraints(
            new Callback(fn () => false),
        ));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraintProvider::class, 'cases')]
    public function itPassesWhenHandledGivenTimesWithGivenCommandConstraints(
        QuantableConstraintProvider $quantise,
    ): void {
        WasHandled::using(fn (stdClass $command): string => 'handled', $this->app);
        $command = new stdClass();

        $quantise->applyTo(fn () => Bus::dispatch($command));

        $this->assertThat($command, $quantise(new WasHandled()->withCommandConstraints(
            new Callback(fn () => true),
        )));
    }

    #[Test]
    public function itCannotDeriveCommandConstraintsFromCommandStrings(): void
    {
        $command = new readonly class
        {
            public function __construct(
                public string $first = 'first',
            ) {}
        };

        $commandConstraints = new WasHandled()->givenOrDerivedObjectConstraints($command::class);

        $this->assertEmpty($commandConstraints);
    }

    #[Test]
    public function itCanDeriveCommandConstraintsFromCommandObjects(): void
    {
        $command = new readonly class
        {
            public function __construct(
                public string $first = 'first',
            ) {}
        };
        $expected = new DeriveConstraintsFromObjectUsingReflection()->__invoke($command);

        $commandConstraints = new WasHandled()->givenOrDerivedObjectConstraints($command);

        $this->assertNotEmpty($commandConstraints);
        $this->assertEquals($expected, $commandConstraints);
    }

    #[Test]
    public function itCanDeriveCommandConstraintsFromCommandObjectsUsingCustomImplementations(): void
    {
        $command = new stdClass();
        $deriveConstraintsFromObject = DeriveConstraintsFromObjectUsingFakes::passingConstraints();
        WasHandled::deriveConstraintsFromObjectUsing($deriveConstraintsFromObject);

        $commandConstraints = new WasHandled()->givenOrDerivedObjectConstraints($command);

        $this->assertEquals($deriveConstraintsFromObject->constraints, $commandConstraints);
    }

    #[Test]
    public function itFailsWhenNotHandledWithDerivedCommandConstraints(): void
    {
        $command = new stdClass();
        WasHandled::using(fn (stdClass $command): string => 'handled', $this->app);
        WasHandled::deriveConstraintsFromObjectUsing(DeriveConstraintsFromObjectUsingFakes::failingConstraints());
        Bus::dispatch($command);

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('command was handled with derived command constraints.');

        $this->assertThat($command, new WasHandled());
    }

    #[Test]
    public function itPassesWhenDispatchedWithDerivedCommandConstraints(): void
    {
        $command = new stdClass();
        WasHandled::using(fn (stdClass $command): string => 'handled', $this->app);
        WasHandled::deriveConstraintsFromObjectUsing(DeriveConstraintsFromObjectUsingFakes::passingConstraints());

        Bus::dispatch($command);

        $this->assertThat($command, new WasHandled());
    }
}
