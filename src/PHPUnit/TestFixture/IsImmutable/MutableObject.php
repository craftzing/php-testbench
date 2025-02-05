<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\TestFixture\IsImmutable;

final class MutableObject
{
    public function __construct(
        private int $value,
    ) {}

    public function change(int $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function equals(self $other): bool
    {
        return $this->asInt() === $other->asInt();
    }

    public function asInt(): int
    {
        return $this->value;
    }
}