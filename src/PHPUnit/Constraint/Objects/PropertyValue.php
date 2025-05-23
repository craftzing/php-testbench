<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Constraint\Objects;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use ReflectionClass;

use function is_object;

final class PropertyValue extends Constraint
{
    public function __construct(
        public readonly string $name,
        public readonly Constraint $constraint,
    ) {}

    #[Override]
    protected function matches(mixed $other): bool
    {
        is_object($other) or throw new InvalidArgumentException(self::class . ' can only be evaluated for objects.');

        $actualObject = new ReflectionClass($other);

        if ($actualObject->hasProperty($this->name) === false) {
            return false;
        }

        $actualPropertyValue = $actualObject->getProperty($this->name)->getValue($other);

        try {
            Assert::assertThat($actualPropertyValue, $this->constraint);
        } catch (ExpectationFailedException) {
            return false;
        }

        return true;
    }

    public function toString(): string
    {
        $comparesTo = Str::of($this->constraint::class)
            ->classBasename()
            ->snake(' ');

        return "`$this->name` property value $comparesTo";
    }
}
