<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Constraint;

use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Util\Exporter;
use Psr\Http\Message\StreamInterface;
use SebastianBergmann\Comparator\Comparator;
use SebastianBergmann\Comparator\ComparisonFailure;

use function assert;

final class StreamInterfaceComparator extends Comparator
{
    public function accepts(mixed $expected, mixed $actual): bool
    {
        return $expected instanceof StreamInterface && $actual instanceof StreamInterface;
    }

    public function assertEquals(mixed $expected, mixed $actual, float $delta = 0.0, bool $canonicalize = false, bool $ignoreCase = false): void
    {
        assert($expected instanceof StreamInterface, 'Expected value is not an instance of ' . StreamInterface::class);
        assert($actual instanceof StreamInterface, 'Actual value is not an instance of ' . StreamInterface::class);

        if ($actual::class !== $expected::class) {
            throw self::comparisonFailure(
                $expected,
                $actual,
                $actual::class . ' is not a ' . $expected::class,
            );
        }

        $isEqual = new IsEqual((string) $expected);

        if ($isEqual->evaluate((string) $actual, returnResult: true)) {
            return;
        }

        throw self::comparisonFailure($expected, $actual, 'Data streams are not equal.');
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
