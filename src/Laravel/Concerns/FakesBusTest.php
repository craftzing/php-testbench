<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Concerns;

use Craftzing\TestBench\Laravel\Extensions\Bus\TestFixture\FakeCommand;
use Craftzing\TestBench\Laravel\TestCase;
use Illuminate\Bus\Dispatcher;
use Illuminate\Contracts\Bus\Dispatcher as BusContract;
use Illuminate\Contracts\Bus\QueueingDispatcher;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Testing\Fakes\BusFake;
use PHPUnit\Framework\Attributes\Test;

final class FakesBusTest extends TestCase
{
    use FakesBus;

    protected function setUp(): void
    {
        $this->refreshApplication();

        $bus = new Dispatcher($this->app);
        $this->app->singleton(QueueingDispatcher::class, fn (): Dispatcher => $bus);
        $this->app->singleton(BusContract::class, fn (): Dispatcher => $bus);

        Bus::setFacadeApplication($this->app);

        parent::setUp();
    }

    #[Test]
    public function itCanSwapTheBusForAFake(): void
    {
        $this->assertInstanceOf(BusFake::class, Bus::getFacadeRoot());
    }

    #[Test]
    public function itCanSwapTheFakeForTheBus(): void
    {
        $this->dontFakeBus();

        $this->assertInstanceOf(Dispatcher::class, Bus::getFacadeRoot());
    }

    #[Test]
    public function itCanCreateAFakeCommandHandler(): void
    {
        $command = new FakeCommand(1);

        $handler = $this->fakeCommandHandling(fn (FakeCommand $command): bool => true);

        $this->assertTrue(Bus::hasCommandHandler($command));
        $this->assertSame($handler, Bus::getCommandHandler($command));
    }
}
