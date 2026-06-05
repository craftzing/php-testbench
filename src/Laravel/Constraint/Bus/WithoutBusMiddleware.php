<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Bus;

use Illuminate\Support\Facades\Bus;

trait WithoutBusMiddleware
{
    abstract public function afterApplicationCreated(callable $callback);

    public function setUpWithoutBusMiddleware(): void
    {
        $this->afterApplicationCreated(static function (): void {
            Bus::pipeThrough([]);
        });
    }
}
