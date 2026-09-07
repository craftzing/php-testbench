<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Saloon\Doubles;

use Craftzing\TestBench\Doubles\Callable\SpyCallable;
use Craftzing\TestBench\PHPUnit\Constraint\Callables\WasCalled;
use Craftzing\TestBench\PHPUnit\Constraint\StreamInterfaceComparator;
use Craftzing\TestBench\PHPUnit\DataProviders\HttpStatusCode;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Auth\NullAuthenticator;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Senders\GuzzleSender;

final class FakeConnectorTest extends TestCase
{
    #[Test]
    public function itCanBeDecoratedAsSpy(): void
    {
        $instance = new FakeConnector();

        $result = $instance->spy();

        $this->assertEquals(new SpyConnector($instance), $result);
    }

    #[Test]
    public function itCanApplyNullAuthentication(): void
    {
        $instance = new FakeConnector();

        $result = $instance->withAuthentication();

        $this->assertNull($instance->getAuthenticator());
        $this->assertEquals(new NullAuthenticator(), $result->getAuthenticator());
    }

    #[Test]
    public function itAlwaysUsesGuzzleSendersToAvoidSideEffectsOfGlobalState(): void
    {
        $instance = new FakeConnector();

        $result = $instance->sender();

        $this->assertEquals(new GuzzleSender(), $result);
    }

    #[Test]
    #[DataProviderExternal(HttpStatusCode::class, 'all')]
    public function itCreatesPendingRequestsThatNeverUseMockClientsToAvoidSideEffectsOfGlobalState(
        HttpStatusCode $httpStatusCode,
    ): void {
        $client = new MockClient();
        $request = new FakeRequest();
        $fakeResponse = new FakeResponse($request, [], $httpStatusCode->code);
        $instance = new FakeConnector($fakeResponse);

        $result = $instance->createPendingRequest($request, $client);

        $this->assertNull($result->getMockClient());
        $this->assertNull($result->withMockClient($client)->getMockClient());
    }

    #[Test]
    public function itFailsWhenSendingRequestsWithoutFakeResponse(): void
    {
        $request = new FakeRequest();
        $instance = new FakeConnector();

        $this->expectExceptionObject(new MissingFakeResponseForRequest($request));

        $instance->send($request);
    }

    #[Test]
    #[DataProviderExternal(HttpStatusCode::class, 'success')]
    #[DataProviderExternal(HttpStatusCode::class, 'redirection')]
    public function itCanSendRequestsWithFakeResponses(HttpStatusCode $httpStatusCode): void
    {
        $this->registerComparator(new StreamInterfaceComparator());
        $request = new FakeRequest();
        $fakeResponse = new FakeResponse($request, ['Some response'], $httpStatusCode->code);
        $instance = new FakeConnector($fakeResponse);

        $result = $instance->send($request);

        $this->assertEquals($fakeResponse->toResponse($instance), $result);
    }

    #[Test]
    #[DataProviderExternal(HttpStatusCode::class, 'errors')]
    public function itFailsWhenSendingRequestsWithFakeErrorResponses(HttpStatusCode $httpStatusCode): void
    {
        $request = new FakeRequest();
        $fakeResponse = new FakeResponse($request, [], $httpStatusCode->code);
        $instance = new FakeConnector($fakeResponse);

        $this->expectExceptionObject($fakeResponse->toRequestException($instance));

        $instance->send($request);
    }

    #[Test]
    #[DataProviderExternal(HttpStatusCode::class, 'all')]
    public function itDoesntExecuteMiddlewareToPreventSideEffectsOfGlobalState(HttpStatusCode $httpStatusCode): void
    {
        $request = new FakeRequest();
        $fakeResponse = new FakeResponse($request, [], $httpStatusCode->code);
        $middleware = new SpyCallable();
        $instance = new FakeConnector($fakeResponse);
        $instance->middleware()->onRequest($middleware);
        $instance->middleware()->onResponse($middleware);
        $instance->middleware()->onFatalException($middleware);

        try {
            $instance->send($request);
        } catch (RequestException) {
            // Regardless of whether an exception is thrown, the middleware should never be called...
        }

        $middleware->assert(new WasCalled()->never());
    }
}
