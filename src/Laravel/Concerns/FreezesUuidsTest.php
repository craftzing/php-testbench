<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Concerns;

use Craftzing\TestBench\Laravel\TestCase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

final class FreezesUuidsTest extends TestCase
{
    use FreezesUuids;

    #[Test]
    public function itCanFreezeUuids(): void
    {
        $this->assertEquals(Str::uuid()->toString(), $this->frozenUuid->toString());
    }
}
