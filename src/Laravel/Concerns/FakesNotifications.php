<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Concerns;

use Craftzing\TestBench\Laravel\Utils\HandleDispatchAssertions;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Traits\ReflectsClosures;
use PHPUnit\Framework\Attributes\Before;

use function is_callable;

/**
 * @mixin TestCase
 */
trait FakesNotifications
{
    use ReflectsClosures;

    #[Before]
    public function fakeNotifications(): void
    {
        $this->afterApplicationCreated(function (): void {
            Notification::fake();
        });
    }

    /**
     * @param class-string|callable(object): void $notification
     */
    private function assertNotificationWasSentTo(mixed $notifiable, string|callable $notification): void
    {
        [$notificationType, $callback] = $this->prepareNotificationTypeAndCallback($notification);

        Notification::assertSentTo($notifiable, $notificationType, $callback);
    }

    /**
     * @param class-string|callable(object): void $notification
     */
    private function assertNotificationWasNotSentTo(mixed $notifiable, string|callable $notification): void
    {
        [$notificationType, $callback] = $this->prepareNotificationTypeAndCallback($notification);

        Notification::assertNotSentTo($notifiable, $notificationType, $callback);
    }

    private function assertNothingSent(): void
    {
        Notification::assertNothingSent();
    }

    /**
     * @param class-string|callable(object): void $notification
     */
    private function assertNotificationSentTimes(string $notification, int $expectedCount): void
    {
        Notification::assertSentTimes($notification, $expectedCount);
    }

    /**
     * @return array<int, class-string|callable>
     */
    private function prepareNotificationTypeAndCallback(string|callable $notification): array
    {
        $notificationType = $notification;
        $callback = null;

        if (is_callable($notification)) {
            $notificationType = $this->firstClosureParameterType($notification);
            $callback = new HandleDispatchAssertions($notification);
        }

        return [$notificationType, $callback];
    }
}
