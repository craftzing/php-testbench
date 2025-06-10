<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\DataProviders;

use Craftzing\TestBench\PHPUnit\Constraint\Callables\WasCalled;
use Craftzing\TestBench\PHPUnit\Constraint\Quantable;
use Craftzing\TestBench\PHPUnit\Doubles\SpyCallable;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use function iterator_count;

/**
 * @codeCoverageIgnore
 */
final class QuantableConstraintTest extends TestCase
{
    private Generator $faker {
        get => Factory::create();
    }

    #[Test]
    public function itCanConstruct(): void
    {
        $method = $this->faker->word();
        $times = $this->faker->randomNumber();

        $instance = new QuantableConstraint($method, $times);

        $this->assertSame($method, $instance->method);
        $this->assertSame($times, $instance->times);
        $this->assertSame($times, $instance->expected);
    }

    #[Test]
    public function itCanConstructWithDifferentExpectations(): void
    {
        $method = $this->faker->word();
        $times = $this->faker->randomNumber();
        $expected = $this->faker->randomNumber();

        $instance = new QuantableConstraint($method, $times, $expected);

        $this->assertSame($method, $instance->method);
        $this->assertSame($times, $instance->times);
        $this->assertSame($expected, $instance->expected);
    }

    #[Test]
    #[TestWith(['times', 2])]
    #[TestWith(['never', 0])]
    #[TestWith(['once', 1])]
    public function itCanBeInvokedOnConstraints(string $method, int $times): void
    {
        $constraint = $this->constraint();
        $instance = new QuantableConstraint($method, $times);

        $instance($constraint);

        $constraint->spy->assert(new WasCalled(function (string $method, int $times) use ($instance): void {
            $this->assertSame($instance->method, $method);
            $this->assertSame($instance->times, $times);
        })->once());
    }

    #[Test]
    public function itCanApplyGivenTimesToCallbacks(): void
    {
        $times = $this->faker->randomNumber(2);
        $instance = new QuantableConstraint('method', $times);
        $collector = new Collection();

        $instance->applyTo($collector->add(...));

        $this->assertCount($times, $collector);
    }

    #[Test]
    public function itCanConstructCases(): void
    {
        $cases = QuantableConstraint::cases();

        $this->assertIsIterable($cases);
        $this->assertSame(3, iterator_count($cases));
    }

    #[Test]
    public function itCanConstructAtLeastOnceCases(): void
    {
        $cases = QuantableConstraint::atLeastOnce();

        $this->assertIsIterable($cases);
        $this->assertSame(2, iterator_count($cases));
    }

    #[Test]
    public function itCanConstructTooFewOrTooManyTimesCases(): void
    {
        $cases = QuantableConstraint::tooFewOrTooManyTimes();

        $this->assertIsIterable($cases);
        $this->assertSame(2, iterator_count($cases));
    }

    private function constraint(): object
    {
        return new class implements Quantable
        {
            public function __construct(
                public SpyCallable $spy = new SpyCallable(),
            ) {}

            public function times(int $count): Quantable
            {
                $this->spy->__invoke('times', $count);

                return $this;
            }

            public function never(): Quantable
            {
                $this->spy->__invoke('never', 0);

                return $this;
            }

            public function once(): Quantable
            {
                $this->spy->__invoke('once', 1);

                return $this;
            }
        };
    }
}
