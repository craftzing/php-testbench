<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Concerns;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use Ramsey\Uuid\UuidInterface;

use function tap;

/**
 * @mixin TestCase
 */
trait FreezesUuids
{
    private UuidInterface $frozenUuid;

    #[Before]
    public function freezeUuid(): UuidInterface
    {
        return tap($this->frozenUuid = Str::uuid(), function (UuidInterface $uuid): void {
            Str::createUuidsUsing(fn () => $uuid);
        });
    }

    #[After]
    public function dontFreezeUuid(): void
    {
        Str::createUuidsNormally();
    }
}
