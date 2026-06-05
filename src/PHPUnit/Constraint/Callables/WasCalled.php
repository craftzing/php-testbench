<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Constraint\Callables;

use Craftzing\TestBench\PHPUnit\Constraint\ProvidesAdditionalFailureDescription;
use Craftzing\TestBench\PHPUnit\Constraint\Quantable;
use Craftzing\TestBench\PHPUnit\Doubles\CallableInvocation;
use Craftzing\TestBench\PHPUnit\Doubles\SpyCallable;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;

use function array_filter;
use function count;
use function is_callable;

final class WasCalled extends Constraint implements Quantable
{
    use ProvidesAdditionalFailureDescription;

    /** @var callable|null */
    public readonly mixed $withArguments;

    public function __construct(
        ?callable $withArguments = null,
        public readonly ?int $times = null,
    ) {
        $this->withArguments = $withArguments;
    }

    public function times(int $count): self
    {
        return new self($this->withArguments, $count);
    }

    public function never(): self
    {
        return new self($this->withArguments, 0);
    }

    public function once(): self
    {
        return new self($this->withArguments, 1);
    }

    #[Override]
    protected function matches(mixed $other): bool
    {
        if (!$other instanceof SpyCallable) {
            throw new InvalidArgumentException(
                self::class . ' can only be evaluated for instances of ' . SpyCallable::class,
            );
        }

        $matchingInvocations = array_filter($other->invocations, $this->wasInvokedWithExpectedArguments(...));

        return match ($this->times) {
            null => $matchingInvocations !== [],
            default => count($matchingInvocations) === $this->times,
        };
    }

    private function wasInvokedWithExpectedArguments(CallableInvocation $invocation): bool
    {
        if (!is_callable($this->withArguments)) {
            return true;
        }

        try {
            ( $this->withArguments )(...$invocation->arguments);
        } catch (ExpectationFailedException $expectationFailed) {
            $this->additionalFailureDescriptions[] = $expectationFailed->getMessage();

            return false;
        }

        return true;
    }

    public function toString(): string
    {
        $message = 'was called';

        if ($this->times !== null) {
            $message .= " {$this->times} time(s)";
        }

        if ($this->withArguments !== null) {
            $message .= ' with given arguments';
        }

        return $message;
    }
}
