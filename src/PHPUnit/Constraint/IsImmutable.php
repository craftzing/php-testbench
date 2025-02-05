<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Constraint;

use Closure;
use PHPUnit\Framework\Constraint\Constraint;
use UnexpectedValueException;

final class IsImmutable extends Constraint
{
    private readonly Closure $additionalAssertions;

    private function __construct(
        private readonly object $newInstance,
        ?callable $additionalAssertions = null,
    ) {
        $this->additionalAssertions = $additionalAssertions ?: fn () => null;
    }

    public static function comparedTo(object $newInstance, ?callable $additionalAssertions = null): self
    {
        return new self($newInstance, $additionalAssertions);
    }

    protected function matches(mixed $other): bool
    {
        if (! $other instanceof ($this->newInstance::class)) {
            throw new UnexpectedValueException('Cannot compare instances of different classes for immutability.');
        }

        if ($this->newInstance === $other) {
            return false;
        }

        ($this->additionalAssertions)($other, $this->newInstance);

        return true;
    }

    public function toString(): string
    {
        return 'is immutable';
    }
}
