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

final class HasErrorOnPathTest extends TestCase
{
    #[Test]
    public function itCanExpectErrorsOfCategoryAuthentication(): void
    {
        $immutableInstance = new HasErrorOnPath('somePath');

        $instance = $immutableInstance->authentication();

        $this->assertNotEquals($immutableInstance, $instance);
        $this->assertSame($immutableInstance->path, $instance->path);
        $this->assertNotEquals($immutableInstance->category, $instance->category);
        $this->assertSame('authentication', $instance->category);
    }

    #[Test]
    public function itCanExpectErrorsOfCategoryAuthorization(): void
    {
        $immutableInstance = new HasErrorOnPath('somePath');

        $instance = $immutableInstance->authorization();

        $this->assertNotEquals($immutableInstance, $instance);
        $this->assertSame($immutableInstance->path, $instance->path);
        $this->assertNotEquals($immutableInstance->category, $instance->category);
        $this->assertSame('authorization', $instance->category);
    }

    #[Test]
    public function itCanExpectErrorsOfCategoryValidation(): void
    {
        $immutableInstance = new HasErrorOnPath('somePath');

        $instance = $immutableInstance->validation();

        $this->assertNotEquals($immutableInstance, $instance);
        $this->assertSame($immutableInstance->path, $instance->path);
        $this->assertNotEquals($immutableInstance->category, $instance->category);
        $this->assertSame('validation', $instance->category);
    }

    #[Test]
    #[TestWith([true], 'Boolean')]
    #[TestWith([1], 'Integers')]
    #[TestWith(['value'], 'String')]
    public function itCannotEvaluateUnsupportedValueTypes(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(HasErrorOnPath::class . ' can only be evaluated for iterable values');

        $this->assertThat($value, new HasErrorOnPath('somePath'));
    }

    #[Test]
    public function ifFailsWhenNoError(): void
    {
        $category = 'category';
        $path = 'somePath';
        $response = ['data' => 'ok'];

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("has error on `$path` of category `$category`");

        $this->assertThat($response, new HasErrorOnPath($path, $category));
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
    public function itFailsWhenErrorNotOnGivenPath(): void
    {
        $category = 'category';
        $path = 'some.path';
        $response = [
            'errors' => [
                [
                    'path' => ['some', 'nested', 'path'],
                    'extensions' => [
                        'category' => $category,
                    ],
                ],
            ],
        ];

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("has error on `$path` of category `$category`");

        $this->assertThat($response, new HasErrorOnPath($path, $category));
    }

    #[Test]
    public function itFailsWhenErrorNotOfGivenCategory(): void
    {
        $category = 'category';
        $path = 'some.path';
        $response = [
            'errors' => [
                [
                    'path' => ['some', 'path'],
                    'extensions' => [
                        'category' => 'different',
                    ],
                ],
            ],
        ];

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("has error on `$path` of category `$category`");

        $this->assertThat($response, new HasErrorOnPath($path, $category));
    }

    public static function responsesWithErrorOnPathOfGivenCategory(): iterable
    {
        $category = Factory::create()->word();
        $path = 'somePath';

        yield 'Array response' => [
            $path,
            $category,
            $response = [
                'errors' => [
                    [
                        'path' => ['somePath'],
                        'extensions' => [
                            'category' => $category,
                        ],
                    ],
                ],
            ],
        ];

        yield 'Collection response' => [
            $path,
            $category,
            new Collection($response),
        ];
    }

    #[Test]
    #[DataProvider('responsesWithErrorOnPathOfGivenCategory')]
    public function itPassesWhenErrorIsOfGivenCategory(string $path, string $category, iterable $response): void
    {
        $this->assertThat($response, new HasErrorOnPath($path, $category));
    }
}
