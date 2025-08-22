<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Bus;

use Craftzing\TestBench\PHPUnit\Constraint\Callables\WasCalled;
use Craftzing\TestBench\PHPUnit\Constraint\Objects\DerivesConstraintsFromObjects;
use Craftzing\TestBench\PHPUnit\Constraint\ProvidesAdditionalFailureDescription;
use Craftzing\TestBench\PHPUnit\Constraint\Quantable;
use Craftzing\TestBench\PHPUnit\Doubles\SpyCallable;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Traits\ReflectsClosures;
use InvalidArgumentException;
use LogicException;
use Override;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use ReflectionClass;

use function class_basename;
use function class_exists;
use function gettype;
use function is_object;
use function is_string;

final class WasHandled extends Constraint implements Quantable
{
    use ProvidesAdditionalFailureDescription;
    use DerivesConstraintsFromObjects;
    use ReflectsClosures;

    private readonly Dispatcher $bus;

    public function __construct(
        public readonly ?int $times = null,
        Constraint ...$constraints,
    ) {
        $this->bus = Bus::getFacadeRoot();
        $this->objectConstraints = $constraints;
    }

    public function times(int $count): self
    {
        return new self($count, ...$this->objectConstraints);
    }

    public function never(): self
    {
        return new self(0, ...$this->objectConstraints);
    }

    public function once(): self
    {
        return new self(1, ...$this->objectConstraints);
    }

    public function withConstraints(Constraint ...$constraints): self
    {
        return new self($this->times, ...$constraints);
    }

    #[Override]
    protected function matches(mixed $other): bool
    {
        $commandName = match (true) {
            is_object($other) => $other::class,
            is_string($other) && class_exists($other) => $other,
            default => throw new InvalidArgumentException(
                self::class . ' can only be evaluated for strings or command instances, got ' . gettype($other) . '.',
            ),
        };
        $command = match ($other) {
            $commandName => new ReflectionClass($other)->newInstanceWithoutConstructor(),
            default => $other,
        };
        $handler = $this->handler($command);

        try {
            $handler->assert(new WasCalled(function (object $handled) use ($command): void {
                foreach ($this->givenOrDerivedObjectConstraints($command) as $constraint) {
                    Assert::assertThat($handled, $constraint);
                }
            }, $this->times));
        } catch (ExpectationFailedException $expectationFailed) {
            $this->additionalFailureDescriptions[] = $expectationFailed->getMessage();

            return false;
        }

        return true;
    }

    private function handler(object $command): SpyCallable
    {
        $handler = $this->bus->getCommandHandler($command);

        if (! $handler instanceof SpyCallable) {
            throw new LogicException(
                'To use the ' . self::class . ' constraint, make sure to call ' . self::class . '::using() first.',
            );
        }

        return $handler;
    }

    public function toString(): string
    {
        return 'command was handled';
    }

    protected function failureDescription(mixed $other): string
    {
        $message = parent::failureDescription($other);

        if ($this->times !== null) {
            $message .= " $this->times time(s)";
        }

        $message .= match (true) {
            $this->objectConstraints !== [] => ' with given command constraints',
            $this->givenOrDerivedObjectConstraints($other) !== [] => ' with derived command constraints',
            default => '',
        };

        return $message;
    }

    /**
     * @template TCommand of object
     * @param callable(TCommand): mixed $handler
     */
    public static function using(callable $handler, Container $container): void
    {
        $commandFQCN = new self()->firstClosureParameterType($handler(...));
        $handlerName = 'Handle' . class_basename($commandFQCN) . 'UsingSpyCallable';

        $container->instance($handlerName, new SpyCallable($handler));
        $container->make(Dispatcher::class)->map([$commandFQCN => $handlerName]);
    }
}
