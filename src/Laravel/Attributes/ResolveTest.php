<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Attributes;

use Craftzing\TestBench\Laravel\Attributes\TestFixture\AlternateFakeService;
use Craftzing\TestBench\Laravel\Attributes\TestFixture\FakeService;
use Craftzing\TestBench\Laravel\Attributes\TestFixture\Service;
use Craftzing\TestBench\Laravel\Attributes\TestFixture\ServiceDecorator;
use Craftzing\TestBench\Laravel\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class ResolveTest extends TestCase
{
    #[Test]
    public function itCanReturnTheInstance(): void
    {
        $this->app->bind(Service::class, FakeService::class);

        $resolved = (new Resolve())(Service::class, $this->app);

        $this->assertInstanceOf(FakeService::class, $resolved);
    }

    #[Test]
    public function itCanSwapAnImplementation(): void
    {
        $this->app->bind(Service::class, FakeService::class);

        $resolved = (new Resolve(swap: Service::class))(AlternateFakeService::class, $this->app);

        $this->assertInstanceOf(AlternateFakeService::class, $resolved);
    }

    #[Test]
    public function itCanResolveAnImplementationWithoutDecorators(): void
    {
        $this->app->bind(Service::class, FakeService::class);
        $this->app->extend(Service::class, fn (Service $service): Service => new ServiceDecorator($service));

        $resolved = (new Resolve(withoutDecorators: true))(Service::class, $this->app);

        $this->assertInstanceOf(FakeService::class, $resolved);
    }

    #[Test]
    public function itCanResolveTheImplementationByAliasNameInsteadOfFQN(): void
    {
        $this->app->bind('service', FakeService::class);

        $resolved = (new Resolve(alias: 'service'))(Service::class, $this->app);

        $this->assertInstanceOf(FakeService::class, $resolved);
    }

    #[Test]
    public function itCanDropTheBindingOfTheImplementation(): void
    {
        $this->app->bind(FakeService::class, fn (): Service => new FakeService(1));

        $resolved = (new Resolve(unbind: true))(FakeService::class, $this->app);

        $this->assertInstanceOf(FakeService::class, $resolved);
        $this->assertNull($resolved->value);
    }

    #[Test]
    public function itCanResolveTheImplementationWithParameters(): void
    {
        $expectedValue = 1;
        $this->app->bind(Service::class, FakeService::class);

        $resolved = (new Resolve(with: ['$value' => $expectedValue]))(FakeService::class, $this->app);

        $this->assertEquals($expectedValue, $resolved->value);
    }

    #[Test]
    public function itCanRebindAnImplementationAsSingleton(): void
    {
        $this->app->bind(FakeService::class, FakeService::class);

        $firstTimeResolved = (new Resolve(singleton: true))(FakeService::class, $this->app);
        $secondTimeResolved = (new Resolve())(FakeService::class, $this->app);

        $this->assertSame($firstTimeResolved, $secondTimeResolved);
    }

    #[Test]
    public function itCanResolveTheImplementationUsingACallback(): void
    {
        $this->app->bind(Service::class, FakeService::class);
        $callback = fn (Service $service): Service => new ServiceDecorator($service);

        $resolved = (new Resolve(using: $callback))(Service::class, $this->app);

        $this->assertInstanceOf(ServiceDecorator::class, $resolved);
    }
}
