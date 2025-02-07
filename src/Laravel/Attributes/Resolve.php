<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Attributes;

use Attribute;
use Closure;
use Illuminate\Foundation\Application;

/**
 * @deprecated 8.3
 * @deprecated This will become obsolete because of property hooks in php 8.4
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Resolve
{
    private ?Closure $using;

    /**
     * @param array<class-string|string, mixed> $with
     * @param ?callable $using Specify a custom resolver for the binding to use (method injection is supported).
     */
    public function __construct(
        private ?string $alias = null,
        private array $with = [],
        private bool $unbind = false,
        private bool $singleton = false,
        private bool $withoutDecorators = false,
        private ?string $swap = null,
        ?callable $using = null,
    ) {
        $this->using = $using ? $using(...) : null;
    }

    public function __invoke(string $abstract, Application $app): mixed
    {
        // If the property is set to be resolved by alias, we should use the alias instead of the abstract
        // classFQN. This is useful when working with fakes that may be bound to multiple abstractions...
        if ($this->alias !== null) {
            $abstract = $this->alias;
        }

        // Remove all decorators for the abstract
        if ($this->withoutDecorators === true) {
            $app->forgetExtenders($abstract);
        }

        // Drop the binding for the abstract in the container if requested...
        if ($this->unbind === true) {
            unset($app[$abstract]);
        }

        // Add optional conditional binding to the abstract...
        $this->addConditionalBindingsToAbstract($abstract, $app);

        // Resolve the instance from the container...
        $instance = match ($this->using) {
            null => $app[$abstract],
            default => $app->call($this->using),
        };

        // Rebind the abstract as a singleton to the container. This should ensure to return
        // the same instance whenever the abstract is resolved from the container...
        if ($this->singleton === true) {
            $app->instance($abstract, $instance);
        }

        // Swap an optional abstract binding in the container for the current abstract...
        if ($this->swap !== null) {
            $app->instance($this->swap, $instance);
        }

        return $instance;
    }

    private function addConditionalBindingsToAbstract(string $abstract, Application $app): void
    {
        foreach ($this->with as $binding => $implementation) {
            $app->when($abstract)
                ->needs($binding)
                ->give($implementation);
        }
    }
}
