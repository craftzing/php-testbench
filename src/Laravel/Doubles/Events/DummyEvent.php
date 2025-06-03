<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Doubles\Events;

/**
 * @codeCoverageIgnore
 */
final readonly class DummyEvent
{
    /**
     * @var array<int, mixed>
     */
    public array $arguments;

    public function __construct(mixed ...$arguments)
    {
        $this->arguments = $arguments;
    }
}
