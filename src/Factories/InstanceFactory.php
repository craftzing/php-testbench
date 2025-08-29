<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Factories;

use ReflectionClass;
use ReflectionProperty;

/**
 * @template TClass
 */
final readonly class InstanceFactory
{
    public function __construct(
        /** @var class-string<TClass> */
        private string $classFQN,
    ) {}

    /**
     * @param array<string, mixed> $attributes
     * @return TClass
     */
    public function make(array $attributes): object
    {
        $instance = new ReflectionClass($this->classFQN)->newInstanceWithoutConstructor();

        foreach ($attributes as $attribute => $value) {
            new ReflectionProperty($this->classFQN, $attribute)->setValue($instance, $value);
        }

        return $instance;
    }
}

