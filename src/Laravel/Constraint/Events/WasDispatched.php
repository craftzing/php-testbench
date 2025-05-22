<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Events;

use Closure;
use Craftzing\TestBench\PHPUnit\Constraint\Quantable;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;

use function array_filter;
use function count;
use function gettype;
use function is_string;

final class WasDispatched extends Constraint implements Quantable
{
    use RequiresEventFake;

    public function __construct(
        public ?Closure $assertEvent = null,
        public ?int $times = null,
    ) {
        $this->eventFake = $this->resolveEventFake();
    }

    public function times(int $count): self
    {
        return new self($this->assertEvent, $count);
    }

    public function never(): self
    {
        return new self($this->assertEvent, 0);
    }

    public function once(): self
    {
        return new self($this->assertEvent, 1);
    }

    #[Override]
    protected function matches(mixed $other): bool
    {
        is_string($other) or throw new InvalidArgumentException(
            self::class . ' can only be evaluated for strings, got ' . gettype($other) . '.',
        );

        // Note that we deliberately don't use the EventFake::assertDispatched() method. Going through the dispatched
        // events ourselves gives us the flexibility to match them against the given event assertions. That way,
        // we can check if an event was dispatched x times matching the event assertions without having to
        // provide "escape hatches" in the event assertion callback for non-matching events...
        $matchingDispatchedEvents = array_filter(
            $this->eventFake->dispatchedEvents()[$other] ?? [],
            $this->matchesEventAssertions(...),
        );

        return match ($this->times) {
            null => $matchingDispatchedEvents !== [],
            default => count($matchingDispatchedEvents) === $this->times,
        };
    }

    private function matchesEventAssertions(array $dispatchedEvent): bool
    {
        [$event] = $dispatchedEvent;

        try {
            $this->assertEvent?->__invoke($event);
        } catch (ExpectationFailedException) {
            return false;
        }

        return true;
    }

    public function toString(): string
    {
        $message = 'event was dispatched';

        if ($this->times !== null) {
            $message .= " $this->times times";
        }

        if ($this->assertEvent !== null) {
            $message .= ' with expected event assertions';
        }

        return $message;
    }
}
