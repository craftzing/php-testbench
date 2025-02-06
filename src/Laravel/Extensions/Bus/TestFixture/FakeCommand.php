<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Extensions\Bus\TestFixture;

final readonly class FakeCommand
{
    public function __construct(
        public int $value,
    ) {}
}