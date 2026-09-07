<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Constraint;

use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Util\Exporter;
use ReflectionClass;
use ReflectionProperty;
use SebastianBergmann\Comparator\Comparator;
use SebastianBergmann\Comparator\ComparisonFailure;

use function assert;
use function is_a;
use function is_object;

final class PublicPropertiesComparator extends Comparator
{
    public function __construct(
        private readonly string $classFQN,
    ) {}

    public function accepts(mixed $expected, mixed $actual): bool
    {
        return is_a($expected, $this->classFQN) && is_a($actual, $this->classFQN);
    }

    public function assertEquals(
        mixed $expected,
        mixed $actual,
        float $delta = 0.0,
        bool $canonicalize = false,
        bool $ignoreCase = false,
    ): void {
        assert(is_object($expected));
        assert(is_object($actual));

        if ($actual::class !== $expected::class) {
            throw self::comparisonFailure(
                $expected,
                $actual,
                $actual::class.' is not a '.$expected::class,
            );
        }

        $isEqual = new IsEqual($this->comparableProperties($expected));

        if ($isEqual->evaluate($this->comparableProperties($actual), returnResult: true)) {
            return;
        }

        throw self::comparisonFailure($expected, $actual, 'Class does not have expected property values');
    }

    /** @return array<string, mixed> */
    private function comparableProperties(object $subject): array
    {
        $properties = [];

        foreach (new ReflectionClass($subject)->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isVirtual()) {
                continue;
            }

            $properties[$property->getName()] = $property->getValue($subject);
        }

        return $properties;
    }

    private static function comparisonFailure(object $expected, object $actual, string $message): ComparisonFailure
    {
        return new ComparisonFailure(
            $expected,
            $actual,
            Exporter::export($expected),
            Exporter::export($actual),
            $message,
        );
    }
}
