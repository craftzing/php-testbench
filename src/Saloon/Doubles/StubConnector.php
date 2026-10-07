<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Saloon\Doubles;

use Saloon\Http\Auth\NullAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\HasMockClient;

final class StubConnector extends Connector
{
    use HasMockClient;

    public function withAuthentication(): self
    {
        // @mago-expect analyzer:deprecated-class
        return new self()->authenticate(new NullAuthenticator());
    }

    public function resolveBaseUrl(): string
    {
        return 'https://fake.localhost';
    }
}
