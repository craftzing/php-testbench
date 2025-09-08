<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Bus;

use Illuminate\Support\Facades\Bus;
use LogicException;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class WithoutBusMiddlewareTest extends TestCase
{
    use WithoutBusMiddleware;

    #[Test]
    public function itCanRemoveBusMiddleware(): void
    {
        Bus::pipeThrough([
            fn (mixed $command, mixed $next): mixed => throw new LogicException('This should not happen'),
        ]);

        $this->setUpWithoutBusMiddleware();

        $result = Bus::dispatch($this->invokableClass());

        $this->assertEquals('handled', $result);
    }

    private function invokableClass(): object
    {
        return new class {
            public function __invoke(): string
            {
                return 'handled';
            }
        };
    }
}
