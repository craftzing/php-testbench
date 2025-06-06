<?php

declare(strict_types=1);

namespace Craftzing\TestBench\PHPUnit\Doubles;

use Craftzing\TestBench\PHPUnit\AssertsConstraints;

use function call_user_func_array;
use function is_callable;

final class SpyCallable
{
    use AssertsConstraints;

    /**
     * @var array<int, CallableInvocation>
     */
    private(set) public array $invocations = [];

    public function __construct(
        public readonly mixed $return = null,
    ) {}

    public function __invoke(mixed ...$arguments): mixed
    {
        $this->invocations[] = new CallableInvocation(...$arguments);

        if (is_callable($this->return)) {
            return call_user_func_array($this->return, $arguments);
        }

        return $this->return;
    }
}
