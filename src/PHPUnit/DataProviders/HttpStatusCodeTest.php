<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\DataProviders;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

use function array_filter;
use function count;
use function iterator_to_array;

use const ARRAY_FILTER_USE_KEY;

final class HttpStatusCodeTest extends TestCase
{
    public static function isNotFound(): iterable
    {
        foreach (Response::$statusTexts as $code => $message) {
            yield "{$code} {$message}" => [
                $code,
                $code === Response::HTTP_NOT_FOUND,
            ];
        }
    }

    #[Test]
    #[DataProvider('isNotFound')]
    public function itReturnsFalseWhenNotFound(int $code, bool $expected): void
    {
        $instance = new HttpStatusCode($code, 'Message');

        $result = $instance->isNotFound();

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function itCanGenerateAllStatusCodes(): void
    {
        $expected = Response::$statusTexts;

        $results = iterator_to_array(HttpStatusCode::all());

        $this->assertContainsStatusTexts($expected, $results);
    }

    #[Test]
    public function itCanGenerateErrorStatusCodes(): void
    {
        $expected = array_filter(
            Response::$statusTexts,
            static fn(int $code): bool => $code >= 400,
            ARRAY_FILTER_USE_KEY,
        );

        $results = iterator_to_array(HttpStatusCode::errors());

        $this->assertContainsStatusTexts($expected, $results);
    }

    #[Test]
    public function itCanGenerateSuccessStatusCodes(): void
    {
        $expected = array_filter(
            Response::$statusTexts,
            static fn(int $code): bool => $code < 300,
            ARRAY_FILTER_USE_KEY,
        );

        $results = iterator_to_array(HttpStatusCode::success());

        $this->assertContainsStatusTexts($expected, $results);
    }

    #[Test]
    public function itCanGenerateRedirectionStatusCodes(): void
    {
        $expected = array_filter(
            Response::$statusTexts,
            static fn(int $code): bool => $code >= 300 && $code < 400,
            ARRAY_FILTER_USE_KEY,
        );

        $results = iterator_to_array(HttpStatusCode::redirection());

        $this->assertContainsStatusTexts($expected, $results);
    }

    #[Test]
    public function itCanGenerateClientErrorStatusCodes(): void
    {
        $expected = array_filter(
            Response::$statusTexts,
            static fn(int $code): bool => $code >= 400 && $code < 500,
            ARRAY_FILTER_USE_KEY,
        );

        $results = iterator_to_array(HttpStatusCode::clientErrors());

        $this->assertContainsStatusTexts($expected, $results);
    }

    #[Test]
    public function itCanGenerateServerErrorStatusCodes(): void
    {
        $expected = array_filter(
            Response::$statusTexts,
            static fn(int $code): bool => $code >= 500,
            ARRAY_FILTER_USE_KEY,
        );

        $results = iterator_to_array(HttpStatusCode::serverErrors());

        $this->assertContainsStatusTexts($expected, $results);
    }

    /**
     * @param array<int, string> $expected
     * @param array<string, array<HttpStatusCode>> $results
     */
    private function assertContainsStatusTexts(array $expected, array $results): void
    {
        $this->assertCount(count($expected), $results);

        foreach ($expected as $code => $message) {
            $this->assertEquals([new HttpStatusCode($code, $message)], $results["{$code} {$message}"]);
        }
    }
}
