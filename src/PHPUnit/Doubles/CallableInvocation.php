<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Doubles;

final readonly class CallableInvocation
{
    /**
     * @var array<int, mixed>
     */
    public array $arguments;

    public function __construct(mixed ...$arguments)
    {
        $this->arguments = $arguments;
    }
}
