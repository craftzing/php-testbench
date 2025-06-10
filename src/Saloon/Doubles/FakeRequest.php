<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Saloon\Doubles;

use Override;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

final class FakeRequest extends Request
{
    public function __construct(
        protected Method $method = Method::GET,
        public readonly string $endpoint = '/fake',
        public readonly mixed $dto = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return $this->endpoint;
    }

    #[Override]
    public function createDtoFromResponse(Response $response): mixed
    {
        return $this->dto;
    }
}
