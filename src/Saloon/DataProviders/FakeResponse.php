<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Saloon\DataProviders;

use LogicException;
use Saloon\Exceptions\Request\ClientException;
use Saloon\Exceptions\Request\Statuses\ForbiddenException;
use Saloon\Exceptions\Request\Statuses\InternalServerErrorException;
use Saloon\Exceptions\Request\Statuses\NotFoundException;
use Saloon\Http\Connector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class FakeResponse
{
    public function __construct(
        private MockResponse $response,
        public string $exceptionFQCN = '',
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
        return new self(
            MockResponse::make(['message' => 'Bad request'], SymfonyResponse::HTTP_BAD_REQUEST),
            ClientException::class,
        );
    }

    public static function forbidden(): self
    {
        return new self(
            MockResponse::make(['message' => 'Forbidden'], SymfonyResponse::HTTP_FORBIDDEN),
            ForbiddenException::class,
        );
    }

    public static function notFound(): self
    {
        return new self(
            MockResponse::make(['message' => 'Not found'], SymfonyResponse::HTTP_NOT_FOUND),
            NotFoundException::class,
        );
    }

    public static function serverError(): self
    {
        return new self(
            MockResponse::make(['message' => 'Server error'], SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR),
            InternalServerErrorException::class,
        );
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
