<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Saloon\Doubles;

use Override;
use PHPUnit\Framework\Constraint\IsEqual;
use Saloon\Http\Auth\NullAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Http\Senders\GuzzleSender;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

final class FakeConnector extends Connector
{
    use AlwaysThrowOnErrors;

    /** @var list<FakeResponse> */
    private array $fakeResponses;

    public function __construct(FakeResponse ...$fakeResponses)
    {
        $this->fakeResponses = $fakeResponses;

        // Override the sender to prevent Saloon from trying tp resolve it through the Laravel plugin. This
        // may happen when running pure unit tests and Laravel unit tests in parallel using Paratest...
        $this->sender = new GuzzleSender();
    }

    public function spy(): SpyConnector
    {
        return new SpyConnector($this);
    }

    public function withAuthentication(): self
    {
        return new self(...$this->fakeResponses)->authenticate(new NullAuthenticator());
    }

    public function resolveBaseUrl(): string
    {
        return 'https://connector.fake';
    }

    #[Override]
    public function send(Request $request, ?MockClient $mockClient = null, ?callable $handleRetry = null): Response
    {
        $fakeResponse = $this->fakeResponseMatchingRequest($request);

        if ($fakeResponse === null) {
            throw new MissingFakeResponseForRequest($request);
        }

        return $fakeResponse->toResponse($this)->throw();
    }

    private function fakeResponseMatchingRequest(Request $request): ?FakeResponse
    {
        foreach ($this->fakeResponses as $fakeResponse) {
            if (new IsEqual($request)->evaluate($fakeResponse->request, returnResult: true)) {
                return $fakeResponse;
            }
        }

        return null;
    }

    public function createPendingRequest(Request $request, ?MockClient $mockClient = null): PendingRequest
    {
        // This Connector uses our own FakeResponse API which doesn't rely on mock clients, so
        // we should never use mock clients when creating new PendingRequest instances...
        return new class($this, $request, $mockClient) extends PendingRequest {
            public function getMockClient(): ?MockClient
            {
                return null;
            }
        };
    }

    public function boot(PendingRequest $pendingRequest): void
    {
        // Flush the middleware pipeline for this request only to prevent global middleware
        // (like event dispatchers) injected by the Laravel plugin. This may happen when
        // running pure unit tests and Laravel unit tests in parallel using Paratest...
        $pendingRequest->middleware()->getRequestPipeline()->setPipes([]);
        $pendingRequest->middleware()->getResponsePipeline()->setPipes([]);
        $pendingRequest->middleware()->getFatalPipeline()->setPipes([]);
    }
}
