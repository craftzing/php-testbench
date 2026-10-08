<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Saloon\Doubles;

use Craftzing\TestBench\Doubles\Callable\SpyCallable;
use Craftzing\TestBench\PHPUnit\Constraint\PublicPropertiesComparator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Saloon\Config;
use Saloon\Contracts\Sender;
use Saloon\Data\Pipe;
use Saloon\Helpers\MiddlewarePipeline;
use Saloon\Helpers\Pipeline;
use Saloon\Http\Auth\BasicAuthenticator;
use Saloon\Http\Auth\NullAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Senders\GuzzleSender;

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

        $this->assertSame($instance->middleware(), $result);
        $this->assertSamePipes($middlewarePipeline->getRequestPipeline(), $result->getRequestPipeline());
        $this->assertSamePipes($middlewarePipeline->getResponsePipeline(), $result->getResponsePipeline());
        $this->assertSamePipes($middlewarePipeline->getFatalPipeline(), $result->getFatalPipeline());
    }

    private function assertSamePipes(Pipeline $expected, Pipeline $actual): void
    {
        $describe = static fn(Pipeline $pipeline): array => array_map(
            static fn(Pipe $pipe): array => [$pipe->callable, $pipe->name, $pipe->order],
            $pipeline->getPipes(),
        );

        $this->assertSame($describe($expected), $describe($actual));
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
        $this->registerComparator(new PublicPropertiesComparator(GuzzleSender::class));
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
