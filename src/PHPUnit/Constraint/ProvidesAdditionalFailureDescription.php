<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Constraint;

use Illuminate\Support\Collection;
use Override;

trait ProvidesAdditionalFailureDescription
{
    /**
     * @var array<string>
     */
    private array $additionalFailureDescriptions = [];

    #[Override]
    protected function additionalFailureDescription(mixed $other): string
    {
        return new Collection($this->additionalFailureDescriptions)
            ->map(fn (string $description): string => "\n* $description\n")
            ->implode('');
    }
}
