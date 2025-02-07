<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Concerns;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Ulid;

use function tap;

/**
 * @mixin TestCase
 */
trait FreezesUlids
{
    private Ulid $frozenUlid;

    #[Before]
    public function freezeUlid(): Ulid
    {
        return tap($this->frozenUlid = Str::ulid(), function (Ulid $ulid): void {
            Str::createUlidsUsing(fn (): Ulid => $ulid);
        });
    }

    #[After]
    public function dontFreezeUlid(): void
    {
        Str::createUlidsNormally();
    }

    private function assertFrozenUlid(Ulid $ulid): void
    {
        $this->assertSame($this->frozenUlid->toBase32(), $ulid->toBase32());
    }
}
