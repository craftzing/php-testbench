<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Saloon\Doubles;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class FakeRequest extends Request
{
    public function __construct(
        protected Method $method = Method::GET,
        private readonly string $endpoint = '/fake',
    ) {}

    public function resolveEndpoint(): string
    {
        return $this->endpoint;
    }
}
