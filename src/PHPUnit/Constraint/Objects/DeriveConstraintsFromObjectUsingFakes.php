<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Constraint\Objects;

use Craftzing\TestBench\PHPUnit\Doubles\SpyCallable;
use PHPUnit\Framework\Constraint\Callback;

final readonly class DeriveConstraintsFromObjectUsingFakes implements DeriveConstraintsFromObject
{
    public SpyCallable $invoke;

    /**
     * @param array<\PHPUnit\Framework\Constraint\Constraint> $constraints
     */
    public function __construct(
        public array $constraints,
    ) {
        $this->invoke = new SpyCallable();
    }

    public static function failingConstraints(): self
    {
        return new self([
            new Callback(fn () => false),
        ]);
    }

    public static function passingConstraints(): self
    {
        return new self([
            new Callback(fn () => true),
        ]);
    }

    public function __invoke(object $object): array
    {
        $this->invoke->__invoke($object);

        return $this->constraints;
    }
}
