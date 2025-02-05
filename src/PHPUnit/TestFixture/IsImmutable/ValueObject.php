<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\TestFixture\IsImmutable;

final readonly class ValueObject
{
    public function __construct(
        private int $value,
    ) {}

    public function change(int $value): self
    {
        return new self($value);
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