<?php

declare(strict_types=1);

namespace Craftzing\TestBench\GraphQL\Constraints;

use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Constraint\Constraint;

use function gettype;
use function implode;
use function is_array;
use function is_iterable;

final class HasErrorOnPath extends Constraint
{
    public function __construct(
        public readonly string $path,
        public readonly string $category = 'graphql',
    ) {}

    public function authentication(): self
    {
        return new self($this->path, 'authentication');
    }

    public function authorization(): self
    {
        return new self($this->path, 'authorization');
    }

    public function validation(): self
    {
        return new self($this->path, 'validation');
    }

    #[Override]
    protected function matches(mixed $other): bool
    {
        $response = match (true) {
            is_array($other) => $other,
            is_iterable($other) => iterator_to_array($other),
            default => throw new InvalidArgumentException(
                self::class . ' can only be evaluated for iterable values, got ' . gettype($other) . '.',
            ),
        };

        foreach ($response['errors'] ?? [] as $error) {
            $path = implode('.', $error['path'] ?? '');
            $category = $error['extensions']['category'] ?? '';

            if ($path !== $this->path) {
                continue;
            }

            if ($category !== $this->category) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function toString(): string
    {
        return "has error on `$this->path` of category `$this->category`";
    }
}
