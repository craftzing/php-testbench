<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Factories;

interface DeferredAttribute
{
    public function __invoke(ImmutableFactory $factory): mixed;
}
