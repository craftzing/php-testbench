<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Eloquent;

use AssertionError;
use Illuminate\Database\Eloquent\Model;
use SebastianBergmann\Comparator\Comparator;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Exporter\Exporter;

use function json_encode;

final class ModelComparator extends Comparator
{
    public function accepts(mixed $expected, mixed $actual): bool
    {
        return $expected instanceof Model && $actual instanceof Model;
    }

    public function assertEquals(
        mixed $expected,
        mixed $actual,
        float $delta = 0.0,
        bool $canonicalize = false,
        bool $ignoreCase = false,
    ): void {
        if (!$expected instanceof Model) {
            throw self::notInstanceOfModel('expected', $expected);
        }

        if (!$actual instanceof Model) {
            throw self::notInstanceOfModel('actual', $actual);
        }

        if ($actual->isNot($expected)) {
            throw new ComparisonFailure(
                $expected,
                $actual,
                $this->serializeModelForException($expected),
                $this->serializeModelForException($actual),
                'Failed asserting that two Eloquent models are equal.',
            );
        }
    }

    private static function notInstanceOfModel(string $argumentName, mixed $value): AssertionError
    {
        return new AssertionError(
            "Argument {$argumentName} must be an instance of " . Model::class . ', received ' . gettype($value) . '.',
        );
    }

    private function serializeModelForException(Model $model): string
    {
        $properties = new Exporter()->toArray($model);

        // Only export properties used to compare the model instances...
        return json_encode([
            'connection' => $properties['connection'],
            'table' => $properties['table'],
            'primaryKey' => $properties['primaryKey'],
            'attributes' => $properties['attributes'],
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }
}
