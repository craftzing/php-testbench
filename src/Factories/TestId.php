<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Factories;

use Illuminate\Support\Str;
use LogicException;
use Throwable;

final readonly class TestId
{
    /**
     * @template T of object
     * @param class-string<T> $classFQN
     * @return T
     */
    public static function generate(string $classFQN): object
    {
        $prefix = Str::of($classFQN)->classBasename()->camel()->value();
        $uid = Str::ulid();

        try {
            return new $classFQN("{$prefix}_{$uid}");
        } catch (Throwable $exception) {
            throw new LogicException(
                'TestIds can only be generated for ID value objects with a read-tolerant default constructor accepting a single argument.',
                previous: $exception,
            );
        }
    }
}
