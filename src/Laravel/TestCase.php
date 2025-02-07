<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel;

use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase as BaseTestCase;

use function array_flip;
use function class_uses_recursive;

abstract class TestCase extends BaseTestCase
{
    protected ?Application $app = null;

    /** @var array<int, callable> */
    protected array $afterApplicationCreatedCallbacks = [];

    protected bool $setUpHasRun = false;

    protected function setUp(): void
    {
        if (! $this->app) {
            $this->refreshApplication();
        }

        $this->runAfterApplicationCreatedCallbacks();

        $this->setUpHasRun = true;
    }

    protected function refreshApplication(): void
    {
        $this->app = $this->createApplication();
    }

    protected function createApplication(): Application
    {
        return new Application();
    }

    protected function runAfterApplicationCreatedCallbacks(): void
    {
        foreach ($this->afterApplicationCreatedCallbacks as $callback) {
            $callback();
        }
    }

    protected function afterApplicationCreated(callable $callback): void
    {
        $this->afterApplicationCreatedCallbacks[] = $callback;

        if ($this->setUpHasRun) {
            $callback();
        }
    }

    /**
     * @return array<int, int|class-string>
     */
    protected function setUpTraits(): array
    {
        return array_flip(class_uses_recursive(static::class));
    }

    protected function tearDown(): void
    {
        $this->app->flush();
        $this->app = null;

        parent::tearDown();
    }
}