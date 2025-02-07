<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Extensions\Bus\TestFixture;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

final class FakeQueueableCommand implements ShouldQueue
{
    use Queueable;
}