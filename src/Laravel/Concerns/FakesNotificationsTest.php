<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Concerns;

use Craftzing\TestBench\Laravel\Concerns\TestFixture\FakeNotification;
use Craftzing\TestBench\Laravel\TestCase;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcherContract;
use Illuminate\Contracts\Notifications\Dispatcher as DispatcherContract;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Notification as Facade;
use Illuminate\Support\Testing\Fakes\NotificationFake;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

final class FakesNotificationsTest extends TestCase
{
    use FakesNotifications;

    protected function setUp(): void
    {
        $this->refreshApplication();
        $this->app->singleton('config', fn () => new Repository([
            'app' => [
                'timezone' => 'Europe/Brussels',
            ],
        ]));

        parent::setUp();

        $app = $this->app;
        $bus = Mockery::mock(BusDispatcherContract::class);
        $bus->shouldReceive('send');
        $app->singleton(BusDispatcherContract::class, fn () => $bus);
        $app->singleton(DispatcherContract::class, fn () => new NotificationFake());
        $manager = Mockery::mock(ChannelManager::class.'[driver]', [$app]);
        $manager->shouldReceive('send');
//        $events->shouldReceive('send');

        Facade::setFacadeApplication($this->app);
    }

    #[Test]
    public function itCanSwapTheChannelManagerForAFake(): void
    {
        $this->assertInstanceOf(NotificationFake::class, Facade::getFacadeRoot());
    }

    #[Test]
    public function itCanAssertANotificationWasSentTo(): void
    {
        $this->assertInstanceOf(NotificationFake::class, Facade::getFacadeRoot());

        $notifiable = new AnonymousNotifiable();
        $notification = new FakeNotification();

        $notifiable->notify($notification);

//        Notification::assertSentTo($notifiable, $notification::class);
        $this->assertNotificationWasSentTo($notification, fn (FakeNotification $notification): bool => true);
    }

    #[Test]
    public function itCanAssertNotificationWasNotSentTo(): void
    {
        $notifiable = new AnonymousNotifiable();
        $notification = new FakeNotification();

        $notifiable->notify($notification);

        $this->assertNotificationWasNotSentTo($notification, FakeNotification::class);
    }
}
