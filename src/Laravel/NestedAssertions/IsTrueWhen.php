<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\NestedAssertions;

use Closure;

final readonly class IsTrueWhen
{
    public function __construct(
        private ?Closure $nestedAssertions = null,
    ) {}

    public function __invoke(mixed ...$arguments): bool
    {
        if ($this->nestedAssertions === null) {
            return true;
        }

        $passes = $this->nestedAssertions->__invoke(...$arguments);

        // If the nested assertion didn't return anything, we should assume the callback used assertions
        // internally instead of comparison. So, we can go ahead and return a truthy value as the
        // callback would never be called if the command didn't get dispatched anyway...
        return match ($passes) {
            null, true => true,
            default => false,
        };
    }
}
