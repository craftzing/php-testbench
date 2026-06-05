<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Constraint\Callables\Assertions;

use PHPUnit\Framework\Assert;

use function count;

final readonly class WithEqualArguments
{
    /** @var array */
    private array $expected;

    public function __construct(mixed ...$expected)
    {
        $this->expected = $expected;
    }

    public function __invoke(mixed ...$actual): void
    {
        Assert::assertCount(count($this->expected), $actual);

        foreach ($actual as $key => $value) {
            Assert::assertEquals($this->expected[$key], $value);
        }
    }
}
