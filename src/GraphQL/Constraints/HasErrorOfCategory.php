<?php

declare(strict_types=1);

namespace Craftzing\TestBench\GraphQL\Constraints;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Constraint\Constraint;

use function gettype;
use function is_array;
use function is_iterable;

final class HasErrorOfCategory extends Constraint
{
    public function __construct(
        public readonly string $category,
        public readonly string $path = 'errors.0.extensions.category',
    ) {}

    public static function authentication(): self
    {
        return new self('authentication');
    }

    public static function authorization(): self
    {
        return new self('authorization');
    }

    public static function validation(): self
    {
        return new self('validation');
    }

    public function path(string $path): self
    {
        return new self($this->category, $path);
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

        return Arr::get($response, $this->path) === $this->category;
    }

    public function toString(): string
    {
        return "has error of category: $this->category ($this->path)";
    }
}
