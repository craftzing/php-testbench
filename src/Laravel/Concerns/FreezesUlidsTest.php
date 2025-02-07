<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Concerns;

use Craftzing\TestBench\Laravel\TestCase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

final class FreezesUlidsTest extends TestCase
{
    use FreezesUlids;

    #[Test]
    public function itCanFreezeUlids(): void
    {
        $this->assertEquals(Str::ulid()->toBase32(), $this->frozenUlid->toBase32());
    }
}
