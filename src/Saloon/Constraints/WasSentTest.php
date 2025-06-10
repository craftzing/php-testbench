<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Saloon\Constraints;

use Craftzing\TestBench\PHPUnit\Constraint\Objects\DeriveConstraintsFromObjectUsingFakes;
use Craftzing\TestBench\PHPUnit\Constraint\Objects\DeriveConstraintsFromObjectUsingReflection;
use Craftzing\TestBench\PHPUnit\DataProviders\QuantableConstraint;
use Craftzing\TestBench\Saloon\Doubles\FakeConnector;
use Craftzing\TestBench\Saloon\Doubles\FakeRequest;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\Constraint\IsIdentical;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;

/**
 * @codeCoverageIgnore
 */
final class WasSentTest extends TestCase
{
    #[Before]
    public function resetDeriveConstraintsFromObjectUsing(): void
    {
        WasSent::deriveConstraintsFromObjectUsing(null);
    }

    #[Test]
    public function itFailsToConstructWithoutMockClients(): void
    {
        $connector = new FakeConnector();

        $this->expectException(LogicException::class);

        new WasSent($connector);
    }

    #[Test]
    public function itCanConstructWithMockClientsOnGivenConnectors(): void
    {
        $connector = new FakeConnector()->withMockClient(new MockClient());

        $instance = new WasSent($connector);

        $this->assertSame($connector, $instance->connector);
        $this->assertNull($instance->times);
        $this->assertEmpty($instance->objectConstraints);
    }

    #[Test]
    public function itCanConstructWithGlobalMockClients(): void
    {
        MockClient::global();
        $connector = new FakeConnector();

        $instance = new WasSent($connector);

        $this->assertSame($connector, $instance->connector);
        $this->assertNull($instance->times);
        $this->assertEmpty($instance->objectConstraints);
    }

    #[Test]
    public function itCanConstructWithConstraints(): void
    {
        $connector = new FakeConnector();
        $constraints = [new IsIdentical('event')];

        $instance = new WasSent($connector)->withConstraints(...$constraints);

        $this->assertSame($connector, $instance->connector);
        $this->assertNull($instance->times);
        $this->assertSame($constraints, $instance->objectConstraints);
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraint::class, 'cases')]
    public function itImplementsTheQuantableInterface(QuantableConstraint $quantise): void
    {
        $connector = new FakeConnector();
        $constraints = [new IsIdentical('event')];
        $instance = new WasSent($connector)->withConstraints(...$constraints);

        $quantisedInstance = $quantise($instance);

        $this->assertSame($connector, $instance->connector);
        $this->assertSame($connector, $quantisedInstance->connector);
        $this->assertNull($instance->times);
        $this->assertSame($quantise->times, $quantisedInstance->times);
        $this->assertSame($constraints, $instance->objectConstraints);
        $this->assertSame($constraints, $quantisedInstance->objectConstraints);
    }

    #[Test]
    #[TestWith([true], 'Boolean')]
    #[TestWith([1], 'Integers')]
    #[TestWith([['event']], 'Array')]
    public function itCannotEvaluateUnsupportedValueTypes(mixed $value): void
    {
        $connector = new FakeConnector()->withMockClient($this->mockClient(FakeRequest::class));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            WasSent::class . ' can only be evaluated for strings or instances of ' . Request::class . '.',
        );

        $this->assertThat($value, new WasSent($connector));
    }

    #[Test]
    public function itFailsWhenNotSent(): void
    {
        $connector = new FakeConnector()->withMockClient($this->mockClient(FakeRequest::class));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('was sent.');

        $this->assertThat(FakeRequest::class, new WasSent($connector));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraint::class, 'cases')]
    public function itPassesWhenSent(QuantableConstraint $quantise): void
    {
        $connector = new FakeConnector()->withMockClient($this->mockClient(FakeRequest::class));

        $quantise->applyTo(fn () => $connector->send(new FakeRequest()));

        $this->assertThat(FakeRequest::class, $quantise(new WasSent($connector)));
    }

    #[Test]
    public function itFailsWhenSentButNotThroughGivenConnector(): void
    {
        $connector = new FakeConnector()->withMockClient($this->mockClient('Different\Request'));
        $differentConnector = new FakeConnector()->withMockClient($this->mockClient(FakeRequest::class));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('was sent.');

        $differentConnector->send(new FakeRequest());

        $this->assertThat(FakeRequest::class, new WasSent($connector));
    }

    #[Test]
    public function itFailsWhenSentButNotWithGivenConstraints(): void
    {
        $connector = new FakeConnector()->withMockClient($this->mockClient(FakeRequest::class));
        $connector->send(new FakeRequest());

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('was sent with given constraints.');

        $this->assertThat(FakeRequest::class, new WasSent($connector)->withConstraints(
            new Callback(fn () => false),
        ));
    }

    #[Test]
    public function itPassesWhenSentWithGivenConstraints(): void
    {
        $connector = new FakeConnector()->withMockClient($this->mockClient(FakeRequest::class));

        $connector->send(new FakeRequest());

        $this->assertThat(FakeRequest::class, new WasSent($connector)->withConstraints(
            new Callback(fn () => true),
        ));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraint::class, 'tooFewOrTooManyTimes')]
    public function itFailsWhenSentButNotGivenTimes(QuantableConstraint $quantise): void
    {
        $connector = new FakeConnector()->withMockClient($this->mockClient(FakeRequest::class));
        $quantise->applyTo(fn () => $connector->send(new FakeRequest()));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("was sent $quantise->expected time(s).");

        $this->assertThat(FakeRequest::class, new WasSent($connector)->times($quantise->expected));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraint::class, 'cases')]
    public function itPassesWhenSentGivenTimes(QuantableConstraint $quantise): void
    {
        $connector = new FakeConnector()->withMockClient($this->mockClient(FakeRequest::class));

        $quantise->applyTo(fn () => $connector->send(new FakeRequest()));

        $this->assertThat(FakeRequest::class, $quantise(new WasSent($connector)));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraint::class, 'tooFewOrTooManyTimes')]
    public function itFailsWhenSentWithGivenConstrainsButNotGivenTimes(QuantableConstraint $quantise): void
    {
        $connector = new FakeConnector()->withMockClient($this->mockClient(FakeRequest::class));
        $quantise->applyTo(fn () => $connector->send(new FakeRequest()));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("was sent $quantise->expected time(s)");

        $this->assertThat(FakeRequest::class, new WasSent($connector)->times($quantise->expected)->withConstraints(
            new Callback(fn () => true),
        ));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraint::class, 'atLeastOnce')]
    public function itFailsWhenSentGivenTimesButNotWithGivenConstrains(QuantableConstraint $quantise): void
    {
        $connector = new FakeConnector()->withMockClient($this->mockClient(FakeRequest::class));
        $quantise->applyTo(fn () => $connector->send(new FakeRequest()));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("was sent $quantise->expected time(s) with given constraints.");

        $this->assertThat(FakeRequest::class, new WasSent($connector)->times($quantise->expected)->withConstraints(
            new Callback(fn () => false),
        ));
    }

    #[Test]
    #[DataProviderExternal(QuantableConstraint::class, 'cases')]
    public function itPassesWhenSentGivenTimesWithGivenConstraints(QuantableConstraint $quantise): void
    {
        $connector = new FakeConnector()->withMockClient($this->mockClient(FakeRequest::class));

        $quantise->applyTo(fn () => $connector->send(new FakeRequest()));

        $this->assertThat(FakeRequest::class, $quantise(new WasSent($connector)->withConstraints(
            new Callback(fn () => true),
        )));
    }

    #[Test]
    public function itCannotDeriveConstraintsFromRequestStrings(): void
    {
        $connector = new FakeConnector()->withMockClient($this->mockClient(FakeRequest::class));

        $constraints = new WasSent($connector)->givenOrDerivedObjectConstraints(FakeRequest::class);

        $this->assertEmpty($constraints);
    }

    #[Test]
    public function itCanDeriveConstraintsFromEventObjects(): void
    {
        $connector = new FakeConnector()->withMockClient($this->mockClient(FakeRequest::class));
        $request = new FakeRequest();
        $expected = new DeriveConstraintsFromObjectUsingReflection()->__invoke($request);

        $constraints = new WasSent($connector)->givenOrDerivedObjectConstraints($request);

        $this->assertNotEmpty($constraints);
        $this->assertEquals($expected, $constraints);
    }

    #[Test]
    public function itCanDeriveConstraintsFromEventObjectsUsingCustomImplementations(): void
    {
        $connector = new FakeConnector()->withMockClient($this->mockClient(FakeRequest::class));
        $request = new FakeRequest();
        $deriveConstraintsFromObject = DeriveConstraintsFromObjectUsingFakes::passingConstraints();
        WasSent::deriveConstraintsFromObjectUsing($deriveConstraintsFromObject);

        $constraints = new WasSent($connector)->givenOrDerivedObjectConstraints($request);

        $this->assertEquals($deriveConstraintsFromObject->constraints, $constraints);
    }

    #[Test]
    public function itFailsWhenNotSentWithDerivedConstraints(): void
    {
        $connector = new FakeConnector()->withMockClient($this->mockClient(FakeRequest::class));
        $request = new FakeRequest();
        WasSent::deriveConstraintsFromObjectUsing(DeriveConstraintsFromObjectUsingFakes::failingConstraints());
        $connector->send($request);

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('was sent with derived constraints.');

        $this->assertThat($request, new WasSent($connector));
    }

    #[Test]
    public function itPassesWhenSentWithDerivedConstraints(): void
    {
        $connector = new FakeConnector()->withMockClient($this->mockClient(FakeRequest::class));
        $request = new FakeRequest();
        WasSent::deriveConstraintsFromObjectUsing(DeriveConstraintsFromObjectUsingFakes::passingConstraints());

        $connector->send($request);

        $this->assertThat($request, new WasSent($connector));
    }

    private function mockClient(string $requestFQCN): MockClient
    {
        return new MockClient([$requestFQCN => MockResponse::make(['message' => 'fake'])]);
    }
}
