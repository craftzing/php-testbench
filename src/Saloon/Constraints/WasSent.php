<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Saloon\Constraints;

use Craftzing\TestBench\PHPUnit\Constraint\Objects\DerivesConstraintsFromObjects;
use Craftzing\TestBench\PHPUnit\Constraint\ProvidesAdditionalFailureDescription;
use Craftzing\TestBench\PHPUnit\Constraint\Quantable;
use InvalidArgumentException;
use LogicException;
use Override;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use Saloon\Http\Connector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Request;
use Saloon\Http\Response;

use function array_filter;
use function array_reduce;
use function count;
use function is_string;

final class WasSent extends Constraint implements Quantable
{
    use DerivesConstraintsFromObjects;
    use ProvidesAdditionalFailureDescription;

    private readonly MockClient $client;

    public function __construct(
        public readonly Connector $connector,
        public readonly ?int $times = null,
        Constraint ...$constraints,
    ) {
        $this->client = $connector->getMockClient() ?: MockClient::getGlobal() ?: throw new LogicException(
            'Missing either a global or connector specific ' . MockClient::class . '.',
        );
        $this->objectConstraints = $constraints;
    }

    public function times(int $count): self
    {
        return new self($this->connector, $count, ...$this->objectConstraints);
    }

    public function never(): self
    {
        return new self($this->connector, 0, ...$this->objectConstraints);
    }

    public function once(): self
    {
        return new self($this->connector, 1, ...$this->objectConstraints);
    }

    public function withConstraints(Constraint ...$constraints): self
    {
        return new self($this->connector, $this->times, ...$constraints);
    }

    #[Override]
    protected function matches(mixed $other): bool
    {
        $requestName = match (true) {
            $other instanceof Request => $other::class,
            is_string($other) => $other,
            default => throw new InvalidArgumentException(
                self::class . ' can only be evaluated for strings or instances of ' . Request::class . '.',
            ),
        };

        $matchingSentRequests = array_reduce($this->client->getRecordedResponses(), function (
            array $matchingSentRequests,
            Response $response,
        ) use ($requestName): array {
            $request = $response->getPendingRequest()->getRequest();

            if ($request::class === $requestName) {
                $matchingSentRequests[] = $request;
            }

            return $matchingSentRequests;
        }, []);
        $sentRequestsMatchingConstraints = array_filter(
            $matchingSentRequests,
            fn (Request $request): bool => $this->matchesRequestConstraints(
                $other,
                $request,
                // When the request was sent exactly once, we should add all nested expectation failures to the
                // failure description in order to provide as much context as possible. We should not to this
                // for requests that were sent more than once, as that would pollute the failure output...
                count($matchingSentRequests) === 1,
            ),
        );

        return match ($this->times) {
            null => $sentRequestsMatchingConstraints !== [],
            default => count($sentRequestsMatchingConstraints) === $this->times,
        };
    }

    private function matchesRequestConstraints(
        string|object $expected,
        object $sentRequest,
        bool $addExpectationFailuresToFailureDescriptions,
    ): bool {
        foreach ($this->givenOrDerivedObjectConstraints($expected) as $matchesConstraint) {
            try {
                Assert::assertThat($sentRequest, $matchesConstraint);
            } catch (ExpectationFailedException $expectationFailed) {
                if ($addExpectationFailuresToFailureDescriptions) {
                    $this->additionalFailureDescriptions[] = $expectationFailed->getMessage();
                }

                return false;
            }
        }

        return true;
    }

    public function toString(): string
    {
        return 'was sent';
    }

    protected function failureDescription(mixed $other): string
    {
        $message = parent::failureDescription($other);

        if ($this->times !== null) {
            $message .= " $this->times time(s)";
        }

        $message .= match (true) {
            $this->objectConstraints !== [] => ' with given constraints',
            $this->givenOrDerivedObjectConstraints($other) !== [] => ' with derived constraints',
            default => '',
        };

        return $message;
    }
}
