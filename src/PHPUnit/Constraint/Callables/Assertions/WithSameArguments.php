<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Constraint\Callables\Assertions;

use PHPUnit\Framework\Assert;

use function count;

final readonly class WithSameArguments
{
    /** @var array */
    private array $expected;

    public function __construct(mixed ...$expected)
    {
        $this->expected = $expected;
    }

    public function __invoke(mixed ...$actual): void
    {
        Assert::assertCount(
            count($this->expected),
            $actual,
            'Callable was invoked with a different amount of arguments.',
        );

        foreach ($actual as $key => $value) {
            // @mago-expect analyzer:invalid-operand
            $argumentNumber = $key + 1;

            Assert::assertSame(
                $this->expected[$key],
                $value,
                "Argument #{$argumentNumber} passed to callable does not match expected value.",
            );
        }
    }
}
