<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Doubles\Events;

/**
 * @codeCoverageIgnore
 */
final readonly class DummyListener
{
    public function __invoke(mixed ...$arguments): void
    {
        //
    }

    public function handle(): void
    {
        //
    }
}
