<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\DataProviders;

use Symfony\Component\HttpFoundation\Response;

final readonly class HttpStatusCode
{
    public function __construct(
        public int $code,
        public string $message,
    ) {}

    public function isNotFound(): bool
    {
        return $this->code === Response::HTTP_NOT_FOUND;
    }

    /** @return iterable<list<self>> */
    public static function all(): iterable
    {
        foreach (Response::$statusTexts as $statusCode => $message) {
            yield "{$statusCode} {$message}" => [
                new self($statusCode, $message),
            ];
        }
    }

    /** @return iterable<list<self>> */
    public static function errors(): iterable
    {
        foreach (Response::$statusTexts as $statusCode => $message) {
            if ($statusCode < Response::HTTP_BAD_REQUEST) {
                continue;
            }

            yield "{$statusCode} {$message}" => [
                new self($statusCode, $message),
            ];
        }
    }

    /** @return iterable<list<self>> */
    public static function success(): iterable
    {
        foreach (Response::$statusTexts as $statusCode => $message) {
            if ($statusCode >= Response::HTTP_MULTIPLE_CHOICES) {
                continue;
            }

            yield "{$statusCode} {$message}" => [
                new self($statusCode, $message),
            ];
        }
    }

    /** @return iterable<list<self>> */
    public static function redirection(): iterable
    {
        foreach (Response::$statusTexts as $statusCode => $message) {
            if ($statusCode < Response::HTTP_MULTIPLE_CHOICES) {
                continue;
            }

            if ($statusCode >= Response::HTTP_BAD_REQUEST) {
                continue;
            }

            yield "{$statusCode} {$message}" => [
                new self($statusCode, $message),
            ];
        }
    }

    /** @return iterable<list<self>> */
    public static function clientErrors(): iterable
    {
        foreach (Response::$statusTexts as $statusCode => $message) {
            if ($statusCode < Response::HTTP_BAD_REQUEST) {
                continue;
            }

            if ($statusCode >= Response::HTTP_INTERNAL_SERVER_ERROR) {
                continue;
            }

            yield "{$statusCode} {$message}" => [
                new self($statusCode, $message),
            ];
        }
    }

    /** @return iterable<list<self>> */
    public static function serverErrors(): iterable
    {
        foreach (Response::$statusTexts as $statusCode => $message) {
            if ($statusCode < Response::HTTP_INTERNAL_SERVER_ERROR) {
                continue;
            }

            yield "{$statusCode} {$message}" => [
                new self($statusCode, $message),
            ];
        }
    }
}
