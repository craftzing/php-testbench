<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Attributes\TestFixture;

final readonly class FakeService implements Service
{
    public function __construct(
        public ?int $value,
    ) {}
}