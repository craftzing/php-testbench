<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Saloon\Doubles;

use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Helpers\RequestExceptionHelper;
use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

use function json_encode;

final readonly class FakeResponse
{
    public function __construct(
        public Request $request,
        public array $body,
        public int $statusCode,
    ) {}

    public static function ok(Request $request, array $body = []): self
    {
        return new self($request, $body, SymfonyResponse::HTTP_OK);
    }

    public static function created(Request $request, array $body = []): self
    {
        return new self($request, $body, SymfonyResponse::HTTP_CREATED);
    }

    public static function noContent(Request $request): self
    {
        return new self($request, [], SymfonyResponse::HTTP_NO_CONTENT);
    }

    public static function badRequest(Request $request, array $body = []): self
    {
        return new self($request, $body, SymfonyResponse::HTTP_BAD_REQUEST);
    }

    public static function notFound(Request $request, array $body = []): self
    {
        return new self($request, $body, SymfonyResponse::HTTP_NOT_FOUND);
    }

    public function toResponse(Connector $connector): Response
    {
        return new Response(
            new Psr7Response($this->statusCode, [], json_encode($this->body, JSON_THROW_ON_ERROR)),
            $connector->createPendingRequest($this->request),
            new Psr7Request($this->request->getMethod()->name, $this->request->resolveEndpoint()),
        );
    }

    public function toRequestException(Connector $connector): RequestException
    {
        return RequestExceptionHelper::create($this->toResponse($connector));
    }
}
