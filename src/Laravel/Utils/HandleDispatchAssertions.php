<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Utils;

use Closure;

final readonly class HandleDispatchAssertions
{
    public function __construct(
        private ?Closure $assertionCallback = null,
    ) {}

    public function __invoke(mixed ...$arguments): bool
    {
        if ($this->assertionCallback === null) {
            return true;
        }

        $passes = $this->assertionCallback->__invoke(...$arguments);

        // If the assertion callback didn't return anything, we should assume the callback used assertions
        // internally instead of comparison. So we can go ahead and return a truthy value as the
        // callback would never be called if the command didn't get dispatched anyway.
        if ($passes === null) {
            return true;
        }

        return $passes;
    }
}
