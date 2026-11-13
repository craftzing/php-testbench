<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Factories;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

use function class_implements;

final class DeferTest extends TestCase
{
    #[Test]
    public function itImplementsDeferredAttribute(): void
    {
        $this->assertContains(DeferredAttribute::class, class_implements(Defer::class));
    }

    public static function closureResults(): iterable
    {
        yield 'String' => ['Resolved'];
        yield 'Integers' => [6];
        yield 'Null' => [null];
        yield 'Instances' => [new stdClass()];
        yield 'Closure' => [static fn() => null];
    }

    public static function closureResultLists(): iterable
    {
        foreach (self::closureResults() as $case => [$result]) {
            yield "List of $case" => [[$result]];
        }
    }

    #[Test]
    #[DataProvider('closureResults')]
    #[DataProvider('closureResultLists')]
    public function itDefersClosures(mixed $expected): void
    {
        $factory = self::createStub(ImmutableFactory::class);
        $instance = new Defer(static fn() => $expected);

        $result = $instance->__invoke($factory);

        $this->assertSame($expected, $result);
    }
}
