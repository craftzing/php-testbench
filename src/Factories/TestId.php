<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Factories;

use Illuminate\Support\Str;
use InvalidArgumentException;
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
            // @mago-expect analyzer:unknown-class-instantiation
            return new $classFQN("{$prefix}_{$uid}");
        } catch (Throwable $exception) {
            throw self::cannotGenerateTestIdForIdClass($exception);
        }
    }

    private static function cannotGenerateTestIdForIdClass(?Throwable $previousException = null): InvalidArgumentException
    {
        return new InvalidArgumentException(
            'TestIds can only be generated for ID value objects with a read-tolerant default constructor accepting a single string argument.',
            previous: $previousException,
        );
    }
}
