<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Saloon\Doubles;

use Craftzing\TestBench\PHPUnit\DataProviders\HttpStatusCode;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Saloon\Exceptions\Request\ClientException;
use Saloon\Exceptions\Request\ServerException;
use Saloon\Http\Connector;
use Saloon\Http\PendingRequest;
use Symfony\Component\HttpFoundation\Response;

final class FakeResponseTest extends TestCase
{
    #[Test]
    public function itCanConstructAsOk(): void
    {
        $request = new FakeRequest();
        $body = ['Some body'];

        $instance = FakeResponse::ok($request, $body);

        $this->assertEquals(new FakeResponse($request, $body, Response::HTTP_OK), $instance);
    }

    #[Test]
    public function itCanConstructAsCreated(): void
    {
        $request = new FakeRequest();
        $body = ['Some body'];

        $instance = FakeResponse::created($request, $body);

        $this->assertEquals(new FakeResponse($request, $body, Response::HTTP_CREATED), $instance);
    }

    #[Test]
    public function itCanConstructAsNoContent(): void
    {
        $request = new FakeRequest();

        $instance = FakeResponse::noContent($request);

        $this->assertEquals(new FakeResponse($request, [], Response::HTTP_NO_CONTENT), $instance);
    }

    #[Test]
    public function itCanConstructAsBadRequest(): void
    {
        $request = new FakeRequest();
        $body = ['Some body'];

        $instance = FakeResponse::badRequest($request, $body);

        $this->assertEquals(new FakeResponse($request, $body, Response::HTTP_BAD_REQUEST), $instance);
    }

    #[Test]
    public function itCanConstructAsNotFound(): void
    {
        $request = new FakeRequest();
        $body = ['Some body'];

        $instance = FakeResponse::notFound($request, $body);

        $this->assertEquals(new FakeResponse($request, $body, Response::HTTP_NOT_FOUND), $instance);
    }

    #[Test]
    #[DataProviderExternal(HttpStatusCode::class, 'all')]
    public function itCanCastToResponses(HttpStatusCode $httpStatusCode): void
    {
        $request = new FakeRequest();
        $pendingRequest = self::createStub(PendingRequest::class);
        $connector = self::createConfiguredStub(Connector::class, ['createPendingRequest' => $pendingRequest]);
        $body = ['Some body'];
        $instance = new FakeResponse($request, $body, $httpStatusCode->code);

        $result = $instance->toResponse($connector);

        $this->assertSame($httpStatusCode->code, $result->status());
        $this->assertSame($body, $result->json());
        $this->assertEquals($pendingRequest, $result->getPendingRequest());
    }

    public static function toException(): iterable
    {
        foreach (HttpStatusCode::errors() as $case => [$httpStatusCode]) {
            yield $case => [
                $httpStatusCode,
                match (true) {
                    $httpStatusCode->code >= Response::HTTP_INTERNAL_SERVER_ERROR => ServerException::class,
                    $httpStatusCode->code >= Response::HTTP_BAD_REQUEST => ClientException::class,
                    default => throw new LogicException("Missing handling of {$httpStatusCode->code} errors."),
                },
            ];
        }
    }

    #[Test]
    #[DataProvider('toException')]
    public function itCanCastToExceptions(HttpStatusCode $httpStatusCode, string $expected): void
    {
        $request = new FakeRequest();
        $connector = self::createStub(Connector::class);
        $instance = new FakeResponse($request, [], $httpStatusCode->code);

        $result = $instance->toRequestException($connector);

        $this->assertInstanceOf($expected, $result);
    }
}
