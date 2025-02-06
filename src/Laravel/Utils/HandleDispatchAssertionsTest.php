<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Utils;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HandleDispatchAssertionsTest extends TestCase
{
    #[Test]
    public function itCanHandleEmptyAssertions(): void
    {
        $handler = new HandleDispatchAssertions();

        $result = $handler();

        $this->assertTrue($result);
    }

    #[Test]
    public function itCanHandleACallbackThatReturnsNothing(): void
    {
        $handler = new HandleDispatchAssertions(function (): void {});

        $result = $handler();

        $this->assertTrue($result);
    }

    #[Test]
    public function itCanHandleACallbackThatReturnsNull(): void
    {
        $handler = new HandleDispatchAssertions(fn (): null => null);

        $result = $handler();

        $this->assertTrue($result);
    }

    public static function assertionCallbacks(): Generator
    {
        yield 'succeeds' => [
            fn (int $equal): bool => $equal === 1,
            true,
        ];
        yield 'fails' => [
            fn (int $odd): bool => $odd % 2 === 0,
            false,
        ];
    }

    #[Test]
    #[DataProvider('assertionCallbacks')]
    public function itCanHandleACallback(callable $callback, bool $expectedResult): void
    {
        $handler = new HandleDispatchAssertions($callback);

        $result = $handler(1);

        $this->assertEquals($expectedResult, $result);
    }
}
