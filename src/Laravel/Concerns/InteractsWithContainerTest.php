<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Concerns;

use Craftzing\TestBench\Laravel\Attributes\Resolve;
use Craftzing\TestBench\Laravel\Attributes\TestFixture\FakeService;
use Craftzing\TestBench\Laravel\Attributes\TestFixture\Service;
use Craftzing\TestBench\Laravel\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class InteractsWithContainerTest extends TestCase
{
    use InteractsWithContainer;

    #[Resolve]
    private Service $service;

    protected function setUp(): void
    {
        $this->refreshApplication();

        $this->app->bind(Service::class, FakeService::class);

        $this->interactWithContainer();

        parent::setUp();
    }

    #[Test]
    public function itCanResolveTheProperty(): void
    {
        $this->assertInstanceOf(FakeService::class, $this->service);
    }
}