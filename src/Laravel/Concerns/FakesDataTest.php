<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Concerns;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

use function array_keys;
use function range;

final class FakesDataTest extends TestCase
{
    use FakesData;

    #[Test]
    public function itCanReturnARandomElement(): void
    {
        $pool = range(0, 9);

        $randomElement = self::random(...$pool);

        $this->assertContains($randomElement, $pool);
    }

    #[Test]
    public function itCanReturnARandomElementExcept(): void
    {
        $pool = range(0, 9);
        $excludedElement = self::random(...$pool);

        $randomElement = self::randomExcept($excludedElement, ...$pool);

        $this->assertNotEquals($excludedElement, $randomElement);
    }

    #[Test]
    public function itCanReturnARandomHttpStatusCode(): void
    {
        $this->assertContains(self::randomHttpStatus(), array_keys(SymfonyResponse::$statusTexts));
    }
}