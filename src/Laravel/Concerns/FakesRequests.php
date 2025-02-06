<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Concerns;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;

trait FakesRequests
{
    private function request(mixed $user = null): Request
    {
        $request = Request::create('/');

        if ($user !== null) {
            $request->setUserResolver(fn () => $user);
        }

        return $request;
    }

    private function nextRequestTestResponse(
        $content = null,
        int $status = Response::HTTP_OK,
        array $headers = [],
    ): Closure {
        return function () use ($content, $status, $headers) {
            return new TestResponse(new Response($content, $status, $headers));
        };
    }
}
