<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Bus;

use Craftzing\TestBench\PHPUnit\DataProviders\QuantableConstraintProvider;
use Craftzing\TestBench\PHPUnit\Doubles\SpyCallable;
use Illuminate\Support\Facades\Bus;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

final class WasHandledTest extends TestCase
{
    public static function callableHandlers(): iterable
    {
        yield 'Closure' => [fn (stdClass $object): string => 'handled'];
        yield 'Invokable class' => [
            new readonly class
            {
                public function __invoke(stdClass $command): string
                {
                    return 'handled';
                }
            },
        ];
    }

    #[Test]
    #[DataProvider('callableHandlers')]
    public function itCanMapGivenCallablesToHandleCommands(callable $handler): void
    {
        WasHandled::using($handler, $this->app);

        $this->assertEquals(new SpyCallable($handler), Bus::getCommandHandler(new stdClass()));
    }

    #[Test]
    public function itCanConstructWithoutArguments(): void
    {
        $instance = new WasHandled();

        $this->assertNull($instance->times);
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraintProvider::class, 'cases')]
    public function itImplementsTheQuantableInterface(QuantableConstraintProvider $quantise): void
    {
        $instance = new WasHandled();

        $quantisedInstance = $quantise($instance);

        $this->assertNull($instance->times);
        $this->assertSame($quantise->times, $quantisedInstance->times);
    }
}
