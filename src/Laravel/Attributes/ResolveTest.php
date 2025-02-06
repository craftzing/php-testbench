<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Attributes;

use Craftzing\TestBench\Laravel\Attributes\TestFixture\AlternateFakeService;
use Craftzing\TestBench\Laravel\Attributes\TestFixture\FakeService;
use Craftzing\TestBench\Laravel\Attributes\TestFixture\Service;
use Craftzing\TestBench\Laravel\Attributes\TestFixture\ServiceDecorator;
use Illuminate\Container\Container;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ResolveTest extends TestCase
{
    #[Test]
    public function itCanReturnTheInstance(): void
    {
        $container = new Container();
        $container->bind(Service::class, FakeService::class);

        $resolved = (new Resolve())(Service::class, $container);

        $this->assertInstanceOf(FakeService::class, $resolved);
    }

    #[Test]
    public function itCanSwapAnImplementation(): void
    {
        $container = new Container();
        $container->bind(Service::class, FakeService::class);

        $resolved = (new Resolve(swap: Service::class))(AlternateFakeService::class, $container);

        $this->assertInstanceOf(AlternateFakeService::class, $resolved);
    }

    #[Test]
    public function itCanResolveAnImplementationWithoutDecorators(): void
    {
        $container = new Container();
        $container->bind(Service::class, FakeService::class);
        $container->extend(Service::class, fn (Service $service): Service => new ServiceDecorator($service));

        $resolved = (new Resolve(withoutDecorators: true))(Service::class, $container);

        $this->assertInstanceOf(FakeService::class, $resolved);
    }

    #[Test]
    public function itCanResolveTheImplementationByAliasNameInsteadOfFQN(): void
    {
        $container = new Container();
        $container->bind('service', FakeService::class);

        $resolved = (new Resolve(alias: 'service'))(Service::class, $container);

        $this->assertInstanceOf(FakeService::class, $resolved);
    }

    #[Test]
    public function itCanDropTheBindingOfTheImplementation(): void
    {
        $container = new Container();
        $container->bind(FakeService::class, fn (): Service => new FakeService(1));

        $resolved = (new Resolve(unbind: true))(FakeService::class, $container);

        $this->assertInstanceOf(FakeService::class, $resolved);
        $this->assertNull($resolved->value);
    }

    #[Test]
    public function itCanResolveTheImplementationWithParameters(): void
    {
        $expectedValue = 1;
        $container = new Container();
        $container->bind(Service::class, FakeService::class);

        $resolved = (new Resolve(with: ['$value' => $expectedValue]))(FakeService::class, $container);

        $this->assertEquals($expectedValue, $resolved->value);
    }

    #[Test]
    public function itCanRebindAnImplementationAsSingleton(): void
    {
        $container = new Container();
        $container->bind(FakeService::class, FakeService::class);

        $firstTimeResolved = (new Resolve(singleton: true))(FakeService::class, $container);
        $secondTimeResolved = (new Resolve())(FakeService::class, $container);

        $this->assertSame($firstTimeResolved, $secondTimeResolved);
    }

    #[Test]
    public function itCanResolveTheImplementationUsingACallback(): void
    {
        $container = new Container();
        $container->bind(Service::class, FakeService::class);
        $callback = fn (Service $service): Service => new ServiceDecorator($service);

        $resolved = (new Resolve(using: $callback))(Service::class, $container);

        $this->assertInstanceOf(ServiceDecorator::class, $resolved);
    }
}
