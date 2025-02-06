<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Concerns;

use Craftzing\TestBench\Laravel\Attributes\Resolve;
use Craftzing\TestBench\Laravel\Attributes\TestFixture\FakeService;
use Craftzing\TestBench\Laravel\Attributes\TestFixture\Service;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InteractsWithContainerTest extends TestCase
{
    use InteractsWithContainer;

    /** @var Application */
    protected $app;

    /** @var array<int, callable> */
    protected $afterApplicationCreatedCallbacks = [];

    #[Resolve]
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $app = new Application();
        $app->bind(Service::class, FakeService::class);
        $this->app = $app;

        $this->interactWithContainer();

        foreach ($this->afterApplicationCreatedCallbacks as $callback) {
            $callback();
        }
    }

    protected function afterApplicationCreated(callable $callback): void
    {
        $this->afterApplicationCreatedCallbacks[] = $callback;
    }

    #[Test]
    public function itCanResolveTheProperty(): void
    {
        $this->assertInstanceOf(FakeService::class, $this->service);
    }
}