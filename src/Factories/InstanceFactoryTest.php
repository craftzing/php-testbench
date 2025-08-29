<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Factories;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionProperty;

final class InstanceFactoryTest extends TestCase
{
    #[Test]
    public function itFailsWhenConstructingInstancesWithPropertiesThatDoNotExist(): void
    {
        $subject = new readonly class('some-id')
        {
            public function __construct(
                public string $id,
            ) {}
        };

        $this->expectException(ReflectionException::class);

        new InstanceFactory($subject::class)->make(['nonsense' => true]);
    }

    #[Test]
    public function itCanConstructInstances(): void
    {
        $attributes = [
            'public' => 'Public',
            'protected' => 'Protected',
            'private' => 'Private',
        ];
        $subject = new readonly class(...$attributes)
        {
            public function __construct(
                public string $public,
                protected string $protected,
                private string $private,
            ) {}

            public function protected(): string
            {
                return $this->protected;
            }

            public function private(): string
            {
                return $this->private;
            }
        };

        $instance = new InstanceFactory($subject::class)->make($attributes);

        $this->assertSame($attributes['public'], $instance->public);
        $this->assertSame($attributes['protected'], $instance->protected());
        $this->assertSame($attributes['private'], $instance->private());
    }

    #[Test]
    public function itConstructsInstancesWithUninitializedPropertiesWhenNotProvidingAttributes(): void
    {
        $subject = new readonly class('some-id')
        {
            public function __construct(
                public string $id,
            ) {}
        };

        $result = new InstanceFactory($subject::class)->make([]);

        $this->assertInstanceOf($subject::class, $result);
        $this->assertFalse(new ReflectionProperty($subject::class, 'id')->isInitialized($result));
    }
}
