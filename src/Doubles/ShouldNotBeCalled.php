<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Doubles;

use LogicException;

final readonly class ShouldNotBeCalled
{
    public function __invoke(): void
    {
        throw new LogicException('This callback should not have been called.');
    }
}
