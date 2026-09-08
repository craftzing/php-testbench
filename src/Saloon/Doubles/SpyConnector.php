<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Saloon\Doubles;

use Craftzing\TestBench\Doubles\Callable\SpyCallable;
use Saloon\Helpers\MiddlewarePipeline;
use Saloon\Http\Connector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Request;
use Saloon\Http\Response;

final class SpyConnector extends Connector
{
    /** @var SpyCallable<null> */
    public readonly SpyCallable $send;

    public function __construct(
        private readonly ?Connector $connector = null,
    ) {
        $this->send = new SpyCallable();
        $this->authenticator = $this->connector?->getAuthenticator();
        $this->middlewarePipeline = $this->connector?->middleware() ?? new MiddlewarePipeline();
        $this->mockClient = $this->connector?->getMockClient();
        $this->sender = $this->connector?->sender() ?? $this->defaultSender();
    }

    public function resolveBaseUrl(): string
    {
        return 'https://connector.spy';
    }

    public function send(Request $request, ?MockClient $mockClient = null, ?callable $handleRetry = null): Response
    {
        $this->send->__invoke($request, $mockClient, $handleRetry);

        if ($this->connector === null) {
            return parent::send($request, $mockClient, $handleRetry);
        }

        return $this->connector->send($request, $mockClient, $handleRetry);
    }
}
