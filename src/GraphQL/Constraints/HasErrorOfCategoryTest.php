<?php

declare(strict_types=1);

namespace Craftzing\TestBench\GraphQL\Constraints;

use Faker\Factory;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

final class HasErrorOfCategoryTest extends TestCase
{
    #[Test]
    public function itCanConstructForAuthenticationErrors(): void
    {
        $instance = HasErrorOfCategory::authentication();

        $this->assertEquals(new HasErrorOfCategory('authentication'), $instance);
    }

    #[Test]
    public function itCanConstructForAuthorizationErrors(): void
    {
        $instance = HasErrorOfCategory::authorization();

        $this->assertEquals(new HasErrorOfCategory('authorization'), $instance);
    }

    #[Test]
    public function itCanSpecifyDifferentPaths(): void
    {
        $path = 'some.different.path';
        $immutableInstance = new HasErrorOfCategory('category');

        $instance = $immutableInstance->path($path);

        $this->assertNotEquals($immutableInstance, $instance);
        $this->assertSame($immutableInstance->category, $instance->category);
        $this->assertSame('errors.0.extensions.category', $immutableInstance->path);
        $this->assertSame($path, $instance->path);
    }

    #[Test]
    #[TestWith([true], 'Boolean')]
    #[TestWith([1], 'Integers')]
    #[TestWith(['value'], 'String')]
    public function itCannotEvaluateUnsupportedValueTypes(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(HasErrorOfCategory::class . ' can only be evaluated for iterable values');

        $this->assertThat($value, new HasErrorOfCategory('unsupported'));
    }

    #[Test]
    #[TestWith([['data' => 'ok']], 'Response array')]
    #[TestWith([new Collection(['data' => 'ok'])], 'Response collection')]
    public function ifFailsWhenNoError(iterable $response): void
    {
        $category = 'category';
        $path = 'errors.0.extensions.category';

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("has error of category: $category ($path)");

        $this->assertThat($response, new HasErrorOfCategory($category));
    }

    public static function responseWithDifferentErrorCategory(): iterable
    {
        $path = 'errors.0.extensions.category';

        yield 'Array response' => [
            $path,
            $response = [
                'errors' => [
                    [
                        'extensions' => [
                            'category' => 'different',
                        ],
                    ],
                ],
            ],
        ];

        yield 'Collection response' => [
            $path,
            new Collection($response),
        ];
    }

    #[Test]
    #[DataProvider('responseWithDifferentErrorCategory')]
    public function itFailsWhenErrorNotOfGivenCategory(string $path, iterable $response): void
    {
        $category = 'category';

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("has error of category: $category ($path)");

        $this->assertThat($response, new HasErrorOfCategory($category, $path));
    }

    public static function responsesWithMatchingErrorCategory(): iterable
    {
        $category = Factory::create()->word();
        $path = 'errors.0.extensions.category';

        yield 'Array response' => [
            $category,
            $path,
            $response = [
                'errors' => [
                    [
                        'extensions' => [
                            'category' => $category,
                        ],
                    ],
                ],
            ],
        ];

        yield 'Collection response' => [
            $category,
            $path,
            new Collection($response),
        ];
    }

    #[Test]
    #[DataProvider('responsesWithMatchingErrorCategory')]
    public function itPassesWhenErrorIsOfGivenCategory(string $category, string $path, iterable $response): void
    {
        $this->assertThat($response, new HasErrorOfCategory($category, $path));
    }
}
