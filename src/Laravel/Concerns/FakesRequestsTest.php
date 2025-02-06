<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Concerns;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FakesRequestsTest extends TestCase
{
    use FakesData;
    use FakesRequests;

    #[Test]
    public function itCanCreateARequest(): void
    {
        $request = $this->request();

        $this->assertEquals('http://localhost', $request->fullUrl());
    }

    #[Test]
    public function itCanCreateARequestWithAUserResolver(): void
    {
        $user = new class ('foo') {
            public function __construct(
                public string $value,
            ) {}
        };

        $request = $this->request($user);

        $this->assertEquals('http://localhost', $request->fullUrl());
        $this->assertSame($user, $request->user());
    }

    #[Test]
    public function itCanCreateTheNextRequestTestResponse(): void
    {
        $resolveTestResponse = $this->nextRequestTestResponse(
            $content = 'foo',
            $responseCode = self::randomHttpStatus(),
            ['Accept' => 'application/json'],
        );

        $response = $resolveTestResponse();

        $this->assertEquals($content, $response->getContent());
        $response->assertStatus($responseCode);
        $response->assertHeader('Accept', 'application/json');
    }
}
