<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\DataProviders;

use Craftzing\TestBench\PHPUnit\Constraint\Quantable;
use Illuminate\Support\Collection;

use function method_exists;
use function random_int;

final readonly class QuantableConstraint
{
    public int $expected;

    public function __construct(
        public string $method,
        public int $times,
        ?int $expected = null,
    ) {
        $this->expected = match ($expected) {
            null => $this->times,
            default => $expected,
        };
    }

    /**
     * @template TConstraint of Quantable
     * @param TConstraint $constraint
     * @return TConstraint
     */
    public function __invoke(Quantable $constraint): Quantable
    {
        if (!method_exists($constraint, $this->method)) {
            throw new \InvalidArgumentException(Quantable::class . "::{$this->method}() does not exist.");
        }

        // @mago-expect analyzer:mixed-return-statement,string-member-selector
        return $constraint->{$this->method}($this->times);
    }

    public function applyTo(callable $callback): void
    {
        Collection::times($this->times, $callback(...));
    }

    /** @return iterable<list<self>> */
    public static function cases(): iterable
    {
        yield 'Never' => [new self('never', 0)];
        yield from self::atLeastOnce();
    }

    /** @return iterable<list<self>> */
    public static function atLeastOnce(): iterable
    {
        yield 'Multiple times' => [new self('times', random_int(2, max: 10))];
        yield 'Once' => [new self('once', 1)];
    }

    /** @return iterable<array<self>> */
    public static function tooFewOrTooManyTimes(): iterable
    {
        yield 'Too few times' => [new self('times', 2, 1)];
        yield 'Too many times' => [new self('times', 2, 3)];
    }
}
