<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Doubles\Callable;

final readonly class CallableInvocation
{
    /** @var array<array-key, mixed> */
    public array $arguments;

    public function __construct(mixed ...$arguments)
    {
        $this->arguments = $arguments;
    }
}
