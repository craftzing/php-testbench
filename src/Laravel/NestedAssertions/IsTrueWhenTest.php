<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\NestedAssertions;

use Craftzing\TestBench\PHPUnit\Doubles\SpyCallable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IsTrueWhenTest extends TestCase
{
    #[Test]
    public function itShouldReturnTruthyWhenThereAreNoNestedAssertions(): void
    {
        $result = new IsTrueWhen()->__invoke();

        $this->assertTrue($result);
    }

    #[Test]
    public function itShouldReturnTruthyWhenNestedAssertionsDontReturnAnything(): void
    {
        $nestedAssertions = function (): void {};

        $result = new IsTrueWhen($nestedAssertions)->__invoke();

        $this->assertTrue($result);
    }

    #[Test]
    public function itShouldReturnTruthyWhenNestedAssertionsReturnTrue(): void
    {
        $nestedAssertions = fn (): true => true;

        $result = new IsTrueWhen($nestedAssertions)->__invoke();

        $this->assertTrue($result);
    }

    #[Test]
    public function itShouldReturnTruthyWhenNestedAssertionsReturnNull(): void
    {
        $nestedAssertions = fn (): null => null;

        $result = new IsTrueWhen($nestedAssertions)->__invoke();

        $this->assertTrue($result);
    }

    #[Test]
    public function itShouldReturnFalsyWhenNestedAssertionsReturnFalse(): void
    {
        $nestedAssertions = fn (): false => false;

        $result = new IsTrueWhen($nestedAssertions)->__invoke();

        $this->assertFalse($result);
    }

    #[Test]
    public function itShouldPassGivenArgumentsToNestedAssertions(): void
    {
        $arguments = [1, 2, 3];
        $nestedAssertions = new SpyCallable();

        $result = new IsTrueWhen($nestedAssertions(...))->__invoke(...$arguments);

        $this->assertTrue($result);
        $nestedAssertions->assertWasCalledOnceWithArguments(...$arguments);
    }
}
