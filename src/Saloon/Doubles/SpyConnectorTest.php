<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Saloon\Doubles;

use Craftzing\TestBench\Doubles\Callable\SpyCallable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Saloon\Config;
use Saloon\Contracts\Sender;
use Saloon\Helpers\MiddlewarePipeline;
use Saloon\Http\Auth\BasicAuthenticator;
use Saloon\Http\Auth\NullAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class SpyConnectorTest extends TestCase
{
    #[Test]
    public function itCanHandleNoAuthenticator(): void
    {
        $instance = new SpyConnector();

        $result = $instance->getAuthenticator();

        $this->assertNull($result);
    }

    #[Test]
    public function itInheritsAuthenticatorFromDecoratedConnector(): void
    {
        $authenticator = new BasicAuthenticator('', '');
        $decoratedConnector = self::createConfiguredStub(Connector::class, ['getAuthenticator' => $authenticator]);
        $instance = new SpyConnector($decoratedConnector);

        $result = $instance->getAuthenticator();

        $this->assertSame($authenticator, $result);
    }

    #[Test]
    public function itCanOverwriteAuthenticatorInheritedFromDecoratedConnector(): void
    {
        $authenticator = new NullAuthenticator();
        $decoratedConnector = self::createConfiguredStub(Connector::class, [
            'getAuthenticator' => new BasicAuthenticator('', ''),
        ]);
        $instance = new SpyConnector($decoratedConnector);

        $result = $instance->authenticate($authenticator)->getAuthenticator();

        $this->assertSame($authenticator, $result);
    }

    #[Test]
    public function itCanHandleNoMiddleware(): void
    {
        $instance = new SpyConnector();

        $result = $instance->middleware();

        $this->assertEquals(new MiddlewarePipeline(), $result);
    }

    #[Test]
    public function itInheritsMiddlewareFromDecoratedConnector(): void
    {
        $middleware = new SpyCallable();
        $middlewarePipeline = new MiddlewarePipeline()
            ->onRequest($middleware)
            ->onResponse($middleware)
            ->onFatalException($middleware);
        $decoratedConnector = self::createConfiguredStub(Connector::class, [
            'middleware' => $middlewarePipeline,
        ]);
        $instance = new SpyConnector($decoratedConnector);

        $result = $instance->middleware();

        $this->assertSame($middlewarePipeline, $result);
    }

    #[Test]
    public function itCanOverwriteInheritedMiddlewareFromDecoratedConnector(): void
    {
        $middleware = new SpyCallable();
        $middlewarePipeline = new MiddlewarePipeline()
            ->onRequest($middleware)
            ->onResponse($middleware)
            ->onFatalException($middleware);
        $decoratedConnector = self::createConfiguredStub(Connector::class, ['middleware' => new MiddlewarePipeline()]);
        $instance = new SpyConnector($decoratedConnector);

        $result = $instance->middleware()->merge($middlewarePipeline);

        $this->assertEquals($middlewarePipeline, $result);
    }

    #[Test]
    public function itCanHandleNoMockClient(): void
    {
        $instance = new SpyConnector();

        $result = $instance->getMockClient();

        $this->assertNull($result);
    }

    #[Test]
    public function itInheritsMockClientFromDecoratedConnector(): void
    {
        $client = new MockClient();
        $decoratedConnector = self::createConfiguredStub(Connector::class, ['getMockClient' => $client]);
        $instance = new SpyConnector($decoratedConnector);

        $result = $instance->getMockClient();

        $this->assertSame($client, $result);
    }

    #[Test]
    public function itCanOverwriteInheritedMockClientFromDecoratedConnector(): void
    {
        $client = new MockClient([self::createStub(MockResponse::class)]);
        $decoratedConnector = self::createConfiguredStub(Connector::class, ['getMockClient' => new MockClient()]);
        $instance = new SpyConnector($decoratedConnector);

        $result = $instance
            ->withMockClient($client)
            ->getMockClient();

        $this->assertSame($client, $result);
    }

    #[Test]
    public function itCanHandleNoDefaultSender(): void
    {
        $instance = new SpyConnector();

        $result = $instance->sender();

        $this->assertEquals(Config::getDefaultSender(), $result);
    }

    #[Test]
    public function itInheritsSenderFromDecoratedConnector(): void
    {
        $sender = self::createStub(Sender::class);
        $decoratedConnector = self::createConfiguredStub(Connector::class, ['sender' => $sender]);
        $instance = new SpyConnector($decoratedConnector);

        $result = $instance->sender();

        $this->assertSame($sender, $result);
    }
}
