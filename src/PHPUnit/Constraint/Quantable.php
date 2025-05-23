<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Constraint;

interface Quantable
{
    public function times(int $count): self;
    public function never(): self;
    public function once(): self;
}
