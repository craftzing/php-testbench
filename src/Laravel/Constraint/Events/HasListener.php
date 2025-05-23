<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Events;

use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Constraint\Constraint;

use function class_exists;
use function gettype;
use function is_callable;
use function is_object;
use function is_string;

final class HasListener extends Constraint
{
    use RequiresEventFake;

    private const string DEFAULT_METHOD = '__invoke';

    public function __construct(
        public string $listener {
            set(string $listener) {
                class_exists($listener) or throw new InvalidArgumentException("$listener is not an existing class.");

                $this->listener = $listener;
            }
        },
        public string $method = self::DEFAULT_METHOD {
            set(string $method) {
                method_exists($this->listener, $method) or throw new InvalidArgumentException(
                    "Method $this->listener::$method does not exist."
                );

                $this->method = $method;
            }
        }
    ) {
        $this->eventFake = $this->resolveEventFake();
    }

    public static function instance(object $listener): self
    {
        return new self($listener::class);
    }

    public static function method(array $listen): self
    {
        is_callable($listen) or throw new InvalidArgumentException('The given listener method is not callable.');
        [$listener, $method] = $listen;

        if (is_object($listener)) {
            $listener = $listener::class;
        }

        return new self($listener, $method);
    }

    #[Override]
    protected function matches(mixed $other): bool
    {
        is_string($other) or throw new InvalidArgumentException(
            self::class . ' can only be evaluated for strings, got ' . gettype($other) . '.',
        );

        Event::assertListening($other, match ($this->method) {
            self::DEFAULT_METHOD => $this->listener,
            default => [$this->listener, $this->method],
        });

        return true;
    }

    public function toString(): string
    {
        return 'has listener';
    }
}
