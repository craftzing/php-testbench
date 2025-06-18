<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel;

use Craftzing\TestBench\GraphQL\Constraints\HasErrorOnPath;
use Illuminate\Testing\TestResponse;
use Orchestra\Testbench\TestCase;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

use function json_encode;

/**
 * @codeCoverageIgnore
 */
final class ServiceProviderTest extends TestCase
{
    #[Override]
    protected function getPackageProviders($app): array
    {
        return [ServiceProvider::class];
    }

    #[Test]
    public function itShouldEnableHasErrorOnPathGraphQlConstraintToHandleTestResponses(): void
    {
        $path = 'somePath';
        $category = 'someCategory';
        $response = new TestResponse(new Response(json_encode([
            'errors' => [
                [
                    'path' => [$path],
                    'extensions' => [
                        'category' => $category,
                    ],
                ],
            ],
        ])));

        $this->assertThat($response, new HasErrorOnPath($path, $category));
    }
}
