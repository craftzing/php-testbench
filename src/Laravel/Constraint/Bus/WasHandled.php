<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Bus;

use Craftzing\TestBench\PHPUnit\Constraint\Quantable;
use Craftzing\TestBench\PHPUnit\Doubles\SpyCallable;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Traits\ReflectsClosures;

use PHPUnit\Framework\Constraint\Constraint;

use function class_basename;

final class WasHandled extends Constraint implements Quantable
{
    use ReflectsClosures;

    public function __construct(
        public readonly ?int $times = null,
    ) {}

    public function times(int $count): Quantable
    {
        return new self($count);
    }

    public function never(): Quantable
    {
        return new self(0);
    }

    public function once(): Quantable
    {
        return new self(1);
    }

    public function toString(): string
    {
        return 'was handled';
    }

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
