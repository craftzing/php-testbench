<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Extensions\Bus;

use Craftzing\TestBench\Laravel\Extensions\Bus\TestFixture\FakeCommand;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FakeCommandHandlerTest extends TestCase
{
    public static function inputValues(): Generator
    {
        yield 'odd' => [1, false];
        yield 'even' => [2, true];
    }

    #[Test]
    #[DataProvider('inputValues')]
    public function itCanHandleACommand(int $input, bool $expectedResult): void
    {
        $command = new FakeCommand($input);
        $handler = new FakeCommandHandler(fn (FakeCommand $command): bool => $command->value % 2 === 0);

        $result = $handler($command);

        $this->assertEquals($expectedResult, $result);
    }

    #[Test]
    public function itCanAssertTheCommandWasHandled(): void
    {
        $command = new FakeCommand(1);
        $handler = new FakeCommandHandler(fn (FakeCommand $command): int => $command->value);
        $handler($command);

        $handler->assertHandled(fn (FakeCommand $command): bool => $command->value === 1);
    }

    #[Test]
    public function itCanAssertTheAmountOfTimesTheCommandWasHandled(): void
    {
        $command = new FakeCommand(1);
        $handler = new FakeCommandHandler(fn (FakeCommand $command): int => $command->value);

        $handler($command);
        $handler($command);
        $handler($command);

        $handler->assertTimesHandled(3);
    }
}