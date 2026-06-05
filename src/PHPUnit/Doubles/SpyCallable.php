<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Doubles;

use Craftzing\TestBench\PHPUnit\AssertsConstraints;

use function call_user_func_array;
use function is_callable;

/** @template TReturn */
final class SpyCallable
{
    use AssertsConstraints;

    /** @var array<int, CallableInvocation> */
    private(set) array $invocations = [];

    public function __construct(
        /** @var TReturn */
        public readonly mixed $return = null,
    ) {}

    /** @return TReturn */
    public function __invoke(mixed ...$arguments): mixed
    {
        $this->invocations[] = new CallableInvocation(...$arguments);

        if (is_callable($this->return)) {
            // @mago-expect analyzer:mixed-return-statement
            return call_user_func_array($this->return, $arguments);
        }

        return $this->return;
    }
}
