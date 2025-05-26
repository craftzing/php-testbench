<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Bus;

use Craftzing\TestBench\PHPUnit\Constraint\Objects\DerivesConstraintsFromObjects;
use Craftzing\TestBench\PHPUnit\Constraint\ProvidesAdditionalFailureDescription;
use Craftzing\TestBench\PHPUnit\Constraint\Quantable;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;

use function count;
use function gettype;
use function is_object;
use function is_string;

final class WasDispatched extends Constraint implements Quantable
{
    use RequiresBusFake;
    use DerivesConstraintsFromObjects;
    use ProvidesAdditionalFailureDescription;

    public function __construct(
        public readonly ?int $times = null,
        Constraint ...$commandConstraints,
    ) {
        $this->busFake = $this->resolveBusFake();
        $this->objectConstraints = $commandConstraints;
    }

    public function times(int $count): self
    {
        return new self($count, ...$this->objectConstraints);
    }

    public function never(): self
    {
        return new self(0, ...$this->objectConstraints);
    }

    public function once(): self
    {
        return new self(1, ...$this->objectConstraints);
    }

    public function withCommandConstraints(Constraint ...$commandConstraints): self
    {
        return new self($this->times, ...$commandConstraints);
    }

    #[Override]
    protected function matches(mixed $other): bool
    {
        $commandName = match (true) {
            is_string($other) => $other,
            is_object($other) => $other::class,
            default => throw new InvalidArgumentException(
                self::class . ' can only be evaluated for strings or command instances, got ' . gettype($other) . '.',
            ),
        };

        // Note that we deliberately don't use the BusFake::assertDispatched() method. Going through the dispatched
        // commands ourselves gives us the flexibility to match them against the given or derived command constraints...
        $matchingDispatchedCommands = $this->busFake->dispatched($commandName);

        $dispatchedEventsMatchingConstraints = $matchingDispatchedCommands->filter(
            fn (object $dispatchedCommand): bool => $this->matchesCommandConstraints(
                $other,
                $dispatchedCommand,
                // When the command was dispatched exactly once, we should add all nested expectation failures to the
                // failure description in order to provide as much context as possible. We should not to this for
                // commands that were dispatched more than once, as that would pollute the failure output...
                count($matchingDispatchedCommands) === 1,
            ),
        );

        return match ($this->times) {
            null => $dispatchedEventsMatchingConstraints->isNotEmpty(),
            default => $dispatchedEventsMatchingConstraints->count() === $this->times,
        };
    }

    private function matchesCommandConstraints(
        string|object $expected,
        object $dispatchedCommand,
        bool $addExpectationFailuresToFailureDescriptions,
    ): bool {
        foreach ($this->givenOrDerivedObjectConstraints($expected) as $matchesConstraint) {
            try {
                Assert::assertThat($dispatchedCommand, $matchesConstraint);
            } catch (ExpectationFailedException $expectationFailed) {
                if ($addExpectationFailuresToFailureDescriptions) {
                    $this->additionalFailureDescriptions[] = $expectationFailed->getMessage();
                }

                return false;
            }
        }

        return true;
    }

    public function toString(): string
    {
        return 'command was dispatched';
    }

    protected function failureDescription(mixed $other): string
    {
        $message = parent::failureDescription($other);

        if ($this->times !== null) {
            $message .= " $this->times time(s)";
        }

        $message .= match (true) {
            $this->objectConstraints !== [] => ' with given command constraints',
            $this->givenOrDerivedObjectConstraints($other) !== [] => ' with derived command constraints',
            default => '',
        };

        return $message;
    }
}
