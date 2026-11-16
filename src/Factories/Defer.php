<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Factories;

use Closure;

final readonly class Defer implements DeferredAttribute
{
    /** @var Closure(ImmutableFactory): mixed */
    private Closure $callback;

    /** @param callable(ImmutableFactory): mixed $callback */
    public function __construct(callable $callback)
    {
        $this->callback = $callback(...);
    }

    public function __invoke(ImmutableFactory $factory): mixed
    {
        return $this->callback->__invoke($factory);
    }
}
