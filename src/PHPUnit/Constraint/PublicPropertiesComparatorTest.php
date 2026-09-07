<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Constraint;

use AssertionError;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Comparator\Comparator;
use SebastianBergmann\Comparator\ComparisonFailure;
use stdClass;

final class PublicPropertiesComparatorTest extends TestCase
{
    #[Test]
    public function itCanBeUsedAsComparator(): void
    {
        $instance = new PublicPropertiesComparator('Class');

        $this->assertInstanceOf(Comparator::class, $instance);
    }

    public static function unacceptableInstances(): iterable
    {
        yield 'Expected instance is not an object' => [
            stdClass::class,
            [],
            new stdClass(),
            AssertionError::class,
        ];

        yield 'Actual instance is not an object' => [
            stdClass::class,
            new stdClass(),
            [],
            AssertionError::class,
        ];

        yield 'Expected instance does not match given class' => [
            stdClass::class,
            new DateTimeImmutable(),
            new stdClass(),
            ComparisonFailure::class,
        ];

        yield 'Actual instance does not match given class' => [
            stdClass::class,
            new stdClass(),
            new DateTimeImmutable(),
            ComparisonFailure::class,
        ];
    }

    #[Test]
    #[DataProvider('unacceptableInstances', validateArgumentCount: false)]
    public function itDoesntAcceptClassesThatDontMatchGivenClasses(
        string $givenClassFQN,
        mixed $expected,
        mixed $actual,
    ): void {
        $instance = new PublicPropertiesComparator($givenClassFQN);

        $result = $instance->accepts($expected, $actual);

        $this->assertFalse($result);
    }

    #[Test]
    public function itAcceptsClassesThatMatchGivenClasses(): void
    {
        $instance = new PublicPropertiesComparator(stdClass::class);

        $result = $instance->accepts(new stdClass(), new stdClass());

        $this->assertTrue($result);
    }

    #[Test]
    #[DataProvider('unacceptableInstances')]
    public function itFailsWhenComparingUnacceptableInstances(
        string $givenClassFQN,
        mixed $expected,
        mixed $actual,
        string $exceptionClassFQN,
    ): void {
        $instance = new PublicPropertiesComparator($givenClassFQN);

        $this->expectException($exceptionClassFQN);

        $instance->assertEquals($expected, $actual);
    }

    #[Test]
    public function itFailsWhenComparingInstancesWithDifferentPublicProperties(): void
    {
        $expected = self::subject();
        $actual = $expected->public('Different');
        $instance = new PublicPropertiesComparator($expected::class);

        $this->expectException(ComparisonFailure::class);

        $instance->assertEquals($expected, $actual);
    }

    public static function isEqual(): iterable
    {
        yield 'Same instances' => [
            $expected = self::subject(),
            $expected,
        ];

        yield 'Equal public properties' => [
            $expected = self::subject(),
            $expected->public($expected->public)
                ->protected('Different Protected')
                ->private('Different Private')
                ->virtual('Different Virtual'),
        ];
    }

    #[Test]
    #[DataProvider('isEqual')]
    public function itPassesWhenComparingInstancesWithEqualPublicProperties(object $expected, object $actual): void
    {
        $instance = new PublicPropertiesComparator($expected::class);

        $this->expectNotToPerformAssertions();

        $instance->assertEquals($expected, $actual);
    }

    private static function subject(): object
    {
        return new class() {
            public string $virtual {
                get => $this->privateVirtual;
            }

            public function __construct(
                public string $public = 'Public',
                protected string $protected = 'Protected',
                private string $private = 'Private',
                private string $privateVirtual = 'Virtual',
            ) {}

            public function public(string $value): self
            {
                return new self($value, $this->protected, $this->private, $this->virtual);
            }

            public function protected(string $value): self
            {
                return new self($this->public, $value, $this->private, $this->virtual);
            }

            public function private(string $value): self
            {
                return new self($this->public, $this->protected, $value, $this->virtual);
            }

            public function virtual(string $value): self
            {
                return new self($this->public, $this->protected, $this->private, $value);
            }
        };
    }
}
