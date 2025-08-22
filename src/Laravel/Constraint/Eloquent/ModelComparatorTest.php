<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Eloquent;

use AssertionError;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Random\Randomizer;
use SebastianBergmann\Comparator\ComparisonFailure;
use stdClass;

use function compact;

/**
 * @codeCoverageIgnore
 */
final class ModelComparatorTest extends TestCase
{
    public static function accepts(): iterable
    {
        yield 'Expected nor actual are models' => [
            'not-a-model',
            'not-a-model',
            false,
        ];

        yield 'Expected is not a model' => [
            new stdClass(),
            self::model('model', 'default'),
            false,
        ];

        yield 'Actual is not a model' => [
            self::model('model', 'default'),
            self::model('model', 'default'),
            true,
        ];
    }

    #[Test]
    #[DataProvider('accepts')]
    public function itOnlyAcceptExpectedAndActualModels(mixed $expected, mixed $actual, bool $accepted): void
    {
        $instance = new ModelComparator();

        $result = $instance->accepts($expected, $actual);

        $this->assertSame($accepted, $result);
    }

    public static function notEqual(): iterable
    {
        yield 'Expected nor actual are models' => [
            'not-a-model',
            'not-a-model',
            AssertionError::class,
        ];

        yield 'Expected is not a model' => [
            new stdClass(),
            self::model('model', 'default'),
            AssertionError::class,
        ];

        yield 'Expected and actual have different IDs' => [
            self::model('model', 'default', Str::uuid()->toString()),
            self::model('model', 'default', Str::uuid()->toString()),
            ComparisonFailure::class,
        ];

        yield 'Expected and actual have different tables' => [
            $expected = self::model('model1', 'default'),
            self::model('model2', 'default', $expected->getKey()),
            ComparisonFailure::class,
        ];

        yield 'Expected and actual have different connections' => [
            $expected = self::model('model', 'default1'),
            self::model('model', 'default2', $expected->getKey()),
            ComparisonFailure::class,
        ];
    }

    #[Test]
    #[DataProvider('notEqual')]
    public function itFailsWhenNotEqual(mixed $expected, mixed $actual, string $exceptionFQCN): void
    {
        $instance = new ModelComparator();

        $this->expectException($exceptionFQCN);

        $instance->assertEquals($expected, $actual);
    }

    public static function isEqual(): iterable
    {
        yield 'Expected and actual are same instance' => [
            $model = self::model('model', 'default'),
            $model,
        ];

        yield 'Expected and actual are different instances' => [
            $model = self::model('model', 'default'),
            self::model('model', 'default', $model->getKey()),
        ];
    }

    #[Test]
    #[DataProvider('isEqual')]
    public function itPassesWhenEqual(Model $expected, Model $actual): void
    {
        $instance = new ModelComparator();

        $this->expectNotToPerformAssertions();

        $instance->assertEquals($expected, $actual);
    }

    private static function model(string $table, string $connection, string $id = ''): Model
    {
        $id ??= new Randomizer()->getInt(1, 10000000000);
        $model = new class(compact('id')) extends Model  {
            protected $fillable = ['id'];
            protected $keyType = 'string';
        };

        return $model->setConnection($connection)->setTable($table);
    }
}
