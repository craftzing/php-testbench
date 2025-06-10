<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Saloon\DataProviders;

use Craftzing\TestBench\Saloon\Doubles\FakeConnector;
use Craftzing\TestBench\Saloon\Doubles\FakeRequest;
use LogicException;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\Connector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Request;
use Saloon\Http\Response as SaloonResponse;
use Symfony\Component\HttpFoundation\Response;

use function is_array;
use function iterator_to_array;
use function json_encode;

/**
 * @codeCoverageIgnore
 */
final class FakeResponseTest extends TestCase
{
    #[Before]
    public function destroyGlobalMockClient(): void
    {
        MockClient::destroyGlobal();
    }

    public static function responses(): iterable
    {
        yield 'Array' => [['message' => 'ok'], Response::HTTP_NO_CONTENT];
        yield 'String' => ['ok', Response::HTTP_ACCEPTED];
    }

    #[Test]
    #[DataProvider('responses')]
    public function itCanFakeResponsesForGivenConnectors(string|array $response, int $status): void
    {
        $connector = new FakeConnector();
        FakeResponse::make($response, $status)->__invoke(FakeRequest::class, $connector);

        $result = $connector->send(new FakeRequest());

        $this->assertSame($status, $result->status());
        $this->assertBody($response, $result);
    }

    #[Test]
    #[DataProvider('responses')]
    public function itShouldNotFakeResponsesForOtherThanGivenConnectors(string|array $response, int $status): void
    {
        $connector = new FakeConnector();
        FakeResponse::make($response, $status)->__invoke(FakeRequest::class, $connector);

        $this->expectException(FatalRequestException::class);

        new FakeConnector()->send(new FakeRequest());
    }

    #[Test]
    #[DataProvider('responses')]
    public function itCanFakeResponsesForAllConnectors(string|array $response, int $status): void
    {
        FakeResponse::make($response, $status)->__invoke(FakeRequest::class);

        $firstResult = new FakeConnector()->send(new FakeRequest());
        $lastResult = new FakeConnector()->send(new FakeRequest());

        $this->assertSame($status, $firstResult->status());
        $this->assertBody($response, $firstResult);
        $this->assertSame($status, $lastResult->status());
        $this->assertBody($response, $lastResult);
    }

    #[Test]
    #[TestWith([new FakeConnector()], 'With given connector instance')]
    #[TestWith([null], 'Without given connector instance')]
    public function itShouldFailRequestsWithoutResponseMock(?Connector $connector): void
    {
        FakeResponse::make(['ok'])->__invoke(FakeRequest::class, $connector);

        $this->expectException(LogicException::class);

        new FakeConnector()->send(new class extends Request
        {
            public function resolveEndpoint(): string
            {
                return 'not-faked';
            }
        });
    }

    #[Test]
    public function itCanProvideCommonErrors(): void
    {
        $cases = iterator_to_array(FakeResponse::commonErrors());

        $this->assertEquals([
            'Bad request' => [FakeResponse::badRequest()],
            'Forbidden' => [FakeResponse::forbidden()],
            'Not found' => [FakeResponse::notFound()],
            'Server error' => [FakeResponse::serverError()],
        ], $cases);
    }

    private function assertBody(string|array $body, SaloonResponse $response): void
    {
        $this->assertSame(match (is_array($body)) {
            true => json_encode($body),
            false => $body,
        }, $response->body());
    }
}
