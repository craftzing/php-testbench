<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint\Constraint;

trait AssertsConstraints
{
    public function assert(Constraint $constraint): void
    {
        Assert::assertThat($this, $constraint);
    }
}
