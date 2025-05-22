<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Concerns;

use Craftzing\TestBench\Laravel\Extensions\Bus\FakeCommandHandler;
use Craftzing\TestBench\Laravel\NestedAssertions\IsTrueWhen;
use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Traits\ReflectsClosures;
use PHPUnit\Framework\Attributes\Before;
use ReflectionException;

use function class_implements;
use function class_uses;
use function is_string;

/**
 * @mixin TestCase
 */
trait FakesBus
{
    use ReflectsClosures;

    #[Before]
    public function rebindCommandBus(): void
    {
        $this->afterApplicationCreated(function (): void {
            Bus::fake($this->commandsToFake ?? []);
        });
    }

    private function dontFakeBus(): void
    {
        Bus::swap($this->app[Dispatcher::class]);
    }

    /**
     * @param class-string|callable(object): void $command
     * @throws ReflectionException
     */
    private function assertBusDispatched(string|callable $command): void
    {
        if (is_string($command)) {
            Bus::assertDispatched($command);

            return;
        }

        Bus::assertDispatched($this->firstClosureParameterType($command), new IsTrueWhen($command));
    }

    private function assertBusDidNotDispatch(string $commandClass): void
    {
        Bus::assertNotDispatched($commandClass);
    }

    private function assertBusDispatchedTimes(string $commandClass, int $times = 1): void
    {
        Bus::assertDispatchedTimes($commandClass, $times);
    }

    private function fakeCommandHandling(callable $handler): FakeCommandHandler
    {
        $this->dontFakeBus();

        $commandType = $this->firstClosureParameterType($handler);

        Bus::map([$commandType => "{$commandType}FakeHandler"]);

        return $this->app->instance("{$commandType}FakeHandler", new FakeCommandHandler($handler));
    }

    public function assertBusQueues(string $command): void
    {
        $this->assertContains(ShouldQueue::class, class_implements($command));
        $this->assertContains(Queueable::class, class_uses($command));
    }
}
