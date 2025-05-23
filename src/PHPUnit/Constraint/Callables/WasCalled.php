<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Constraint\Callables;

use Closure;
use Craftzing\TestBench\PHPUnit\Constraint\Quantable;
use Craftzing\TestBench\PHPUnit\Doubles\CallableInvocation;
use Craftzing\TestBench\PHPUnit\Doubles\SpyCallable;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;

use function array_filter;
use function count;

final class WasCalled extends Constraint implements Quantable
{
    public function __construct(
        public readonly ?Closure $assertInvocation = null,
        public readonly ?int $times = null,
    ) {}

    public function times(int $count): self
    {
        return new self($this->assertInvocation, $count);
    }

    public function never(): self
    {
        return new self($this->assertInvocation, 0);
    }

    public function once(): self
    {
        return new self($this->assertInvocation, 1);
    }

    #[Override]
    protected function matches(mixed $other): bool
    {
        $other instanceof SpyCallable or throw new InvalidArgumentException(
            self::class . ' can only be evaluated for instances of ' . SpyCallable::class,
        );

        $matchingInvocations = array_filter($other->invocations, $this->matchesInvocationAssertions(...));

        return match ($this->times) {
            null => $matchingInvocations !== [],
            default => count($matchingInvocations) === $this->times,
        };
    }

    private function matchesInvocationAssertions(CallableInvocation $invocation): bool
    {
        try {
            $this->assertInvocation?->__invoke(...$invocation->arguments);
        } catch (ExpectationFailedException) {
            return false;
        }

        return true;
    }

    public function toString(): string
    {
        $message = 'was called';

        if ($this->times !== null) {
            $message .= " $this->times times";
        }

        if ($this->assertInvocation !== null) {
            $message .= ' with expected invocation assertions';
        }

        return $message;
    }
}
