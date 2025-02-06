<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Concerns;

use Craftzing\TestBench\Laravel\Attributes\Resolve;
use Illuminate\Foundation\Testing\TestCase;
use PHPUnit\Framework\Attributes\After;
use ReflectionClass;
use ReflectionProperty;

/**
 * @deprecated 8.3
 * @deprecated This will become obsolete because of property hooks in php 8.4
 * @mixin TestCase
 */
trait InteractsWithContainer
{
    /**
     * @var array[]
     */
    private array $propertiesToUnset = [];

    public function interactWithContainer(): void
    {
        $this->afterApplicationCreated(function (): void {
            $test = new ReflectionClass($this);

            foreach ($test->getProperties() as $property) {
                $resolve = $this->resolveAttribute($property);

                if (! $resolve) {
                    continue;
                }

                $resolve($property->getType()->getName(), $this->app);

                $this->{$property->getName()} = $this->app[$property->getType()->getName()];
                $this->propertiesToUnset[] = $property->getName();
            }
        });
    }

    private function resolveAttribute(ReflectionProperty $property): ?Resolve
    {
        $reflectionAttribute = $property->getAttributes(Resolve::class)[0] ?? null;

        if (! $reflectionAttribute) {
            return null;
        }

        $attribute = $reflectionAttribute->newInstance();

        if (! $attribute instanceof Resolve) {
            return null;
        }

        return $attribute;
    }

    #[After]
    public function unsetResolvedProperties(): void
    {
        foreach ($this->propertiesToUnset as $propertyName) {
            unset($this->{$propertyName});
        }
    }
}
