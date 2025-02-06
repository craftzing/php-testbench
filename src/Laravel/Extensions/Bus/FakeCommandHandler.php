<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Extensions\Bus;

use Closure;
use PHPUnit\Framework\Assert;

use function call_user_func;

final class FakeCommandHandler
{
    /**
     * @var array<mixed>|null
     */
    private ?array $argumentsWhenHandled = null;
    private int $handleCounter = 0;

    public function __construct(
        private readonly Closure $handle,
    ) {}

    public function __invoke(mixed ...$arguments): mixed
    {
        $this->handleCounter++;
        $this->argumentsWhenHandled = $arguments;

        return call_user_func($this->handle, ...$arguments);
    }

    public function assertHandled(?callable $commandAssertions = null): void
    {
        Assert::assertNotNull($this->argumentsWhenHandled, 'Command was not handled.');

        if ($commandAssertions) {
            $commandAssertions(...$this->argumentsWhenHandled);
        }
    }

    public function assertTimesHandled(int $count): void
    {
        Assert::assertSame($count, $this->handleCounter);
    }
}