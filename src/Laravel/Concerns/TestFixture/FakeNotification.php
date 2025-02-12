<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Concerns\TestFixture;

use Illuminate\Notifications\Notification;

final class FakeNotification extends Notification
{
    public function getKey(): int
    {
        return 1;
    }

    /**
     * @return array<int, string>
     */
    public function via(): array
    {
        return ['mail'];
    }
}