<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel;

use Craftzing\TestBench\GraphQL\Constraints\HasErrorOnPath;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Illuminate\Testing\TestResponse;

final class ServiceProvider extends IlluminateServiceProvider
{
    public function boot(): void
    {
        HasErrorOnPath::resolveResponseUsing(fn (TestResponse $response): array => $response->json());
    }
}
