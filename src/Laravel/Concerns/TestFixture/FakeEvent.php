<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Concerns\TestFixture;

final readonly class FakeEvent
{
    public function __construct(
        public int $value = 0,
    ) {}
}