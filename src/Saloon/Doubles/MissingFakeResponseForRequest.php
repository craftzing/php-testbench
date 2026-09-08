<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Saloon\Doubles;

use Craftzing\TestBench\PHPUnit\Constraint\PublicPropertiesComparator;
use LogicException;
use PHPUnit\Util\Exporter;
use Saloon\Http\Request;

final class MissingFakeResponseForRequest extends LogicException
{
    public function __construct(Request $request)
    {
        $hint = 'Did you forget to register [' . PublicPropertiesComparator::class . '] as a custom PHPUnit comparator for Saloon Requests?';

        parent::__construct("\n" . Exporter::export($request) . "\n\n{$hint}");
    }
}
