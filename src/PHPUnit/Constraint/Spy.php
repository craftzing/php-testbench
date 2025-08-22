<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Constraint;

use Craftzing\TestBench\PHPUnit\Doubles\SpyCallable;
use PHPUnit\Framework\Constraint\Constraint;

final class Spy extends Constraint
{
    private function __construct(
        public readonly SpyCallable $matches,
    ) {}

    public static function passing(): self
    {
        return new self(new SpyCallable(true));
    }

    public static function failing(): self
    {
        return new self(new SpyCallable(false));
    }

    protected function matches(mixed $other): bool
    {
        return $this->matches->__invoke($other);
    }

    public function toString(): string
    {
        return 'constraint matches as Spy was configured to fail';
    }
}
