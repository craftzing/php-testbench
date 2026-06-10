<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Doubles\Events;

final readonly class DummyEvent
{
    /** @var array<array-key, mixed> */
    public array $arguments;

    public function __construct(mixed ...$arguments)
    {
        $this->arguments = $arguments;
    }
}
