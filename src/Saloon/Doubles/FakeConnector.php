<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Saloon\Doubles;

use Saloon\Http\Auth\NullAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\HasMockClient;

/**
 * @deprecated since v1.3
 * @see \Craftzing\TestBench\Saloon\Doubles\FakeResponseConnector
 * TODO v2: Remove in favour of the new APIs
 */
final class FakeConnector extends Connector
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
