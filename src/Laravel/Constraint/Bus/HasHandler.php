<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Bus;

use Craftzing\TestBench\PHPUnit\Constraint\ProvidesAdditionalFailureDescription;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Bus;
use InvalidArgumentException;
use PHPUnit\Framework\Constraint\Constraint;
use ReflectionClass;

use function class_exists;
use function gettype;
use function is_string;

final class HasHandler extends Constraint
{
    use ProvidesAdditionalFailureDescription;

    private readonly Dispatcher $bus;

    public function __construct(
        /* @var class-string */
        private readonly string $handlerClassFQN,
    ) {
        $this->bus = Bus::getFacadeRoot();
    }

    protected function matches(mixed $other): bool
    {
        is_string($other) or throw new InvalidArgumentException(
            self::class . ' can only be evaluated for strings, got ' . gettype($other) . '.',
        );
        class_exists($other) or throw new InvalidArgumentException(
            self::class . " can only be evaluated for existing classes, got $other.",
        );
        $message = new ReflectionClass($other)->newInstanceWithoutConstructor();
        $actualHandler = $this->bus->getCommandHandler($message);

        if ($actualHandler === false) {
            $this->additionalFailureDescriptions[] = "$other has no handler mapped to it.";

            return false;
        }

        if ($actualHandler::class !== $this->handlerClassFQN) {
            $this->additionalFailureDescriptions[] = "$other has a different handler mapped to it.";

            return false;
        }

        return true;
    }

    public function toString(): string
    {
        return 'has handler';
    }
}
