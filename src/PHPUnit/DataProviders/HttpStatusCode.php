<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\DataProviders;

use Symfony\Component\HttpFoundation\Response;

final readonly class HttpStatusCode
{
    private const int MAX_CODE = 600;

    public function __construct(
        public int $code,
        public string $message,
    ) {}

    public function isNotFound(): bool
    {
        return $this->code === Response::HTTP_NOT_FOUND;
    }

    /** @return iterable<list<self>> */
    private static function generate(int $includedMinCode, int $excludedMaxCode): iterable
    {
        foreach (Response::$statusTexts as $statusCode => $message) {
            if ($statusCode < $includedMinCode) {
                continue;
            }

            if ($statusCode >= $excludedMaxCode) {
                continue;
            }

            yield "{$statusCode} {$message}" => [
                new self($statusCode, $message),
            ];
        }
    }

    /** @return iterable<list<self>> */
    public static function all(): iterable
    {
        return self::generate(Response::HTTP_CONTINUE, self::MAX_CODE);
    }

    /** @return iterable<list<self>> */
    public static function errors(): iterable
    {
        return self::generate(Response::HTTP_BAD_REQUEST, self::MAX_CODE);
    }

    /** @return iterable<list<self>> */
    public static function success(): iterable
    {
        return self::generate(Response::HTTP_CONTINUE, Response::HTTP_MULTIPLE_CHOICES);
    }

    /** @return iterable<list<self>> */
    public static function redirection(): iterable
    {
        return self::generate(Response::HTTP_MULTIPLE_CHOICES, Response::HTTP_BAD_REQUEST);
    }

    /** @return iterable<list<self>> */
    public static function clientErrors(): iterable
    {
        return self::generate(Response::HTTP_BAD_REQUEST, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /** @return iterable<list<self>> */
    public static function serverErrors(): iterable
    {
        return self::generate(Response::HTTP_INTERNAL_SERVER_ERROR, self::MAX_CODE);
    }
}
