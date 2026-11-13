<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Faker\Providers;

use Faker\Provider\Base;

use function array_shift;
use function array_values;
use function serialize;

/** @template TOption */
final class Cycle extends Base
{
    /** @var array<string, list<TOption>> */
    private array $queues = [];

    /**
     * @param TOption $first
     * @param TOption $second
     * @param TOption ...$additional
     * @return TOption
     */
    public function cycle(mixed $first, mixed $second, mixed ...$additional): mixed
    {
        $options = [$first, $second, ...$additional];
        $key = serialize($options);
        $queue = $this->queues[$key] ?? array_values($options);
        $option = array_shift($queue);
        $queue[] = $option;
        $this->queues[$key] = $queue;

        return $option;
    }
}
