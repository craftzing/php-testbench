<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Events;

use Craftzing\TestBench\PHPUnit\Constraint\Objects\DerivesConstraintsFromObjects;
use Craftzing\TestBench\PHPUnit\Constraint\ProvidesAdditionalFailureDescription;
use Craftzing\TestBench\PHPUnit\Constraint\Quantable;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;

use function array_filter;
use function count;
use function gettype;
use function is_object;
use function is_string;

final class WasDispatched extends Constraint implements Quantable
{
    use RequiresEventFake;
    use DerivesConstraintsFromObjects;
    use ProvidesAdditionalFailureDescription;

    public function __construct(
        public readonly ?int $times = null,
        Constraint ...$eventConstraints,
    ) {
        $this->eventFake = $this->resolveEventFake();
        $this->objectConstraints = $eventConstraints;
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

    public function withEventConstraints(Constraint ...$eventConstraints): self
    {
        return new self($this->times, ...$eventConstraints);
    }

    #[Override]
    protected function matches(mixed $other): bool
    {
        $eventName = match (true) {
            is_string($other) => $other,
            is_object($other) => $other::class,
            default => throw new InvalidArgumentException(
                self::class . ' can only be evaluated for strings or event instances, got ' . gettype($other) . '.',
            ),
        };

        // Note that we deliberately don't use the EventFake::assertDispatched() method. Going through the dispatched
        // events ourselves gives us the flexibility to match them against the given or derived event constraints...
        $matchingDispatchedEvents = $this->eventFake->dispatchedEvents()[$eventName] ?? [];

        $dispatchedEventsMatchingConstraints = array_filter(
            $matchingDispatchedEvents,
            fn (array $dispatchedEvent): bool => $this->matchesEventConstraints(
                $other,
                $dispatchedEvent[0],
                // When the event was dispatched exactly once, we should add all nested expectation failures to the
                // failure description in order to provide as much context as possible. We should not to this for
                // events that were dispatched more than once, as that would pollute the failure output...
                count($matchingDispatchedEvents) === 1,
            ),
        );

        return match ($this->times) {
            null => $dispatchedEventsMatchingConstraints !== [],
            default => count($dispatchedEventsMatchingConstraints) === $this->times,
        };
    }

    private function matchesEventConstraints(
        string|object $expected,
        string|object $dispatchedEvent,
        bool $addExpectationFailuresToFailureDescriptions,
    ): bool {
        foreach ($this->givenOrDerivedObjectConstraints($expected) as $matchesConstraint) {
            try {
                Assert::assertThat($dispatchedEvent, $matchesConstraint);
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
        return 'event was dispatched';
    }

    protected function failureDescription(mixed $other): string
    {
        $message = parent::failureDescription($other);

        if ($this->times !== null) {
            $message .= " $this->times time(s)";
        }

        $message .= match (true) {
            $this->objectConstraints !== [] => ' with given event constraints',
            $this->givenOrDerivedObjectConstraints($other) !== [] => ' with derived event constraints',
            default => '',
        };

        return $message;
    }
}
