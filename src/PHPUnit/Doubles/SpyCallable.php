<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Doubles;

use PHPUnit\Framework\Assert;

use function call_user_func_array;
use function is_callable;

final class SpyCallable
{
    /**
     * @var array<int, CallableInvocation>
     */
    private(set) array $invocations = [];

    public function __construct(
        public readonly mixed $return = null,
    ) {}

    public function __invoke(mixed ...$arguments): mixed
    {
        $this->invocations[] = new CallableInvocation(...$arguments);

        if (is_callable($this->return)) {
            return call_user_func_array($this->return, $arguments);
        }

        return $this->return;
    }

    public function assertWasCalled(?callable $assertions = null, string $message = ''): void
    {
        if ($message !== '') {
            $message = "\n$message";
        }

        Assert::assertNotEmpty($this->invocations, "SpyCallable was not called as expected.$message");

        if ($assertions === null) {
            return;
        }

        foreach ($this->invocations as $invocation) {
            $assertions(...$invocation);
        }
    }

    public function assertWasCalledTimes(int $amount): void
    {
        Assert::assertCount($amount, $this->invocations);
    }

    public function assertWasCalledOnce(): void
    {
        $this->assertWasCalledTimes(1);
    }

    public function assertWasCalledOnceWithArguments(mixed ...$expectedArguments): void
    {
        $matchingInvocations = [];

        foreach ($this->invocations as $invocation) {
            // The == is intentional
            if ($invocation == $expectedArguments) {
                $matchingInvocations[] = $invocation;
            }
        }

        Assert::assertNotEmpty(
            $matchingInvocations,
            'SpyCallable was never called with the provided arguments.',
        );
        Assert::assertCount(
            1,
            $matchingInvocations,
            'SpyCallable was called multiple times with the provided arguments. Expected to be called only once.',
        );
    }

    public function assertWasCalledOnceWithEqualArguments(mixed ...$expectedArguments): void
    {
        $matchingInvocations = [];

        foreach ($this->invocations as $invocation) {
            if ($invocation === $expectedArguments) {
                $matchingInvocations[] = $invocation;
            }
        }

        Assert::assertNotEmpty(
            $matchingInvocations,
            'SpyCallable was never called with the provided arguments.',
        );
        Assert::assertCount(
            1,
            $matchingInvocations,
            'SpyCallable was called multiple times with the provided arguments. Expected to be called only once.',
        );
    }

    public function assertWasNotCalled(): void
    {
        Assert::assertEmpty($this->invocations, 'SpyCallable was called unexpectedly.');
    }
}
