<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Faker\Providers;

use Faker\Provider\Base;
use InvalidArgumentException;

use function array_shift;
use function array_values;
use function count;
use function serialize;

final class Cycle extends Base
{
    /** @var array<string, list<mixed>> */
    private array $queues = [];

    /**
     * @template T
     * @param non-empty-array<T> $options
     * @return T
     */
    public function cycle(array $options): mixed
    {
        if (count($options) < 2) {
            throw new InvalidArgumentException('Cannot cycle through lists with less than 2 options.');
        }

        $key = serialize($options);
        $queue = $this->queues[$key] ?? array_values($options);
        $option = array_shift($queue);
        $queue[] = $option;
        $this->queues[$key] = $queue;

        return $option;
    }
}
