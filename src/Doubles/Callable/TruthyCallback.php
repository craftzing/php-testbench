<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Doubles\Callable;

final readonly class TruthyCallback
{
    public function __invoke(): true
    {
        return true;
    }
}
