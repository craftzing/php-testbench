<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Bus;

use Illuminate\Support\Facades\Bus;

trait WithoutBusMiddleware
{
    public function setUpWithoutBusMiddleware(): void
    {
        $this->afterApplicationCreated(function (): void {
            Bus::pipeThrough([]);
        });
    }
}
