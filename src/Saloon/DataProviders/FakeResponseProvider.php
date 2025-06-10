<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Saloon\DataProviders;

use LogicException;
use Saloon\Http\Connector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class FakeResponseProvider
{
    public function __construct(
        private MockResponse $response,
    ) {}

    /**
     * @param string|array<mixed> $response
     */
    public static function make(string|array $response, int $status = SymfonyResponse::HTTP_OK): self
    {
        return new self(MockResponse::make($response, $status));
    }

    public static function badRequest(): self
    {
        return new self(MockResponse::make(['message' => 'Bad request'], SymfonyResponse::HTTP_BAD_REQUEST));
    }

    public static function forbidden(): self
    {
        return new self(MockResponse::make(['message' => 'Forbidden'], SymfonyResponse::HTTP_FORBIDDEN));
    }

    public static function notFound(): self
    {
        return new self(MockResponse::make(['message' => 'Not found'], SymfonyResponse::HTTP_NOT_FOUND));
    }

    public static function serverError(): self
    {
        return new self(MockResponse::make(['message' => 'Server error'], Response::HTTP_INTERNAL_SERVER_ERROR));
    }

    public function __invoke(string $requestFQCN, ?Connector $connector = null): void
    {
        $client = match ($connector) {
            // When not given a connector instance, we should use the global
            // MockClient to fake responses for all connectors...
            null => MockClient::getGlobal() ?: MockClient::global(),

            // When given a connector instance, we should use its specific MockClient
            // to only fake responses for that specific connector instance...
            default => $connector->getMockClient() ?: new MockClient(),
        };

        $client->addResponses([
            $requestFQCN => $this->response,
            '*' => function (PendingRequest $pendingRequest): void {
                throw new LogicException("Missing response mock for {$pendingRequest->getUrl()}.");
            },
        ]);

        $connector?->withMockClient($client);
    }

    /**
     * @return iterable<array<self>>
     */
    public static function commonErrors(): iterable
    {
        yield 'Bad request' => [self::badRequest()];
        yield 'Forbidden' => [self::forbidden()];
        yield 'Not found' => [self::notFound()];
        yield 'Server error' => [self::serverError()];
    }
}
