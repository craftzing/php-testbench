<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Factories;

use ArgumentCountError;
use Error;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;
use Throwable;
use TypeError;

use function class_alias;
use function class_exists;

final class TestIdTest extends TestCase
{
    #[After]
    public function createUlidsNormally(): void
    {
        Str::createUlidsNormally();
    }

    #[Test]
    public function itGeneratesInstancesOfGivenClasses(): void
    {
        $ulid = new Ulid();
        Str::createUlidsUsing(static fn (): Ulid => $ulid);
        $classFQN = self::namedIdClass('FakeId');

        $instance = TestId::generate($classFQN);

        $this->assertSame("fakeId_{$ulid}", $instance->value);
        $this->assertInstanceOf($classFQN, $instance);
    }

    public static function unsupportedClasses(): iterable
    {
        yield 'Constructor rejecting the value' => [
            new readonly class ('accepted') {
                public function __construct(string $value)
                {
                    if ($value !== 'accepted') {
                        throw new InvalidArgumentException("Rejected `{$value}`.");
                    }
                }
            }::class,
            InvalidArgumentException::class,
        ];

        yield 'Constructor requiring more arguments' => [
            new readonly class ('value', 'secondary') {
                public function __construct(
                    public string $value,
                    public string $secondary,
                ) {}
            }::class,
            ArgumentCountError::class,
        ];

        yield 'Constructor expecting another type' => [
            new readonly class (1) {
                public function __construct(
                    public int $value,
                ) {}
            }::class,
            TypeError::class,
        ];

        yield 'Non-existing class' => [
            __NAMESPACE__ . '\\NonExistingId',
            Error::class,
        ];
    }

    #[Test]
    #[DataProvider('unsupportedClasses')] /** @param class-string<Throwable> $expectedPrevious */
    public function itFailsForClassesItCannotInstantiate(string $classFQN, string $expectedPrevious): void
    {
        try {
            TestId::generate($classFQN);
        } catch (Throwable $exception) {
            $this->assertInstanceOf(LogicException::class, $exception);
            $this->assertSame(
                'TestIds can only be generated for ID value objects with a read-tolerant default constructor accepting a single string argument.',
                $exception->getMessage(),
            );
            $this->assertInstanceOf($expectedPrevious, $exception->getPrevious());
        }
    }

    /** @return class-string */
    private static function idClass(): string
    {
        return new readonly class ('') {
            public function __construct(
                public string $value,
            ) {}
        }::class;
    }

    /**
     * Anonymous class names don't have a meaningful basename, so we
     * should assert prefixes against aliases with a readable names.
     *
     * @return class-string
     */
    private static function namedIdClass(string $basename): string
    {
        $alias = __NAMESPACE__ . "\\{$basename}";

        if (! class_exists($alias, autoload: false)) {
            class_alias(self::idClass(), $alias);
        }

        return $alias;
    }
}
