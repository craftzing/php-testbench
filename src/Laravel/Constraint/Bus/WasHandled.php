<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Bus;

use Craftzing\TestBench\PHPUnit\Doubles\SpyCallable;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Traits\ReflectsClosures;

use function class_basename;

final readonly class WasHandled
{
    use ReflectsClosures;

    /**
     * @template TCommand of object
     * @param callable(TCommand): mixed $handler
     */
    public static function using(callable $handler, Container $container): void
    {
        $commandFQCN = new self()->firstClosureParameterType($handler(...));
        $handlerName = 'Handle' . class_basename($commandFQCN) . 'UsingSpyCallable';

        $container->instance($handlerName, new SpyCallable($handler));
        $container->make(Dispatcher::class)->map([$commandFQCN => $handlerName]);
    }
}
