<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Bus;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Testing\Fakes\BusFake;
use LogicException;

trait RequiresBusFake
{
    private readonly BusFake $busFake;

    /**
     * @param class-string ...$commandsToSpyOn
     */
    public static function spy(string ...$commandsToSpyOn): void
    {
        Bus::fake($commandsToSpyOn);
    }

    private function resolveBusFake(): BusFake
    {
        $bus = Bus::getFacadeRoot();

        $bus instanceof BusFake or throw new LogicException(
            'To use the ' . self::class . ' constraint, make sure to call ' . self::class . '::spy() first.',
        );

        return $bus;
    }
}
