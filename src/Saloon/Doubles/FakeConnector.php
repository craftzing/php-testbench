<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Saloon\Doubles;

use Saloon\Http\Connector;
use Saloon\Traits\HasMockClient;

final class FakeConnector extends Connector
{
    use HasMockClient;

    public function resolveBaseUrl(): string
    {
        return 'https://fake.localhost';
    }
}
