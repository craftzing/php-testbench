<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Doubles\Callable;

final readonly class FalsyCallback
{
    public function __invoke(): false
    {
        return false;
    }
}
