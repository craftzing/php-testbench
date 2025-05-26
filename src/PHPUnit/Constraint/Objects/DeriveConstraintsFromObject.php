<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Constraint\Objects;

use PHPUnit\Framework\Constraint\Constraint;

interface DeriveConstraintsFromObject
{
    /**
     * @return array<Constraint>
     */
    public function __invoke(object $object): array;
}
