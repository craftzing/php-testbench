<?php

declare(strict_types=1);

namespace Craftzing\TestBench\Laravel\Constraint\Events;

use Craftzing\TestBench\Laravel\Doubles\Events\DummyEvent;
use Craftzing\TestBench\Laravel\Doubles\Events\DummyListener;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use LogicException;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\ExpectationFailedException;

final class HasListenerTest extends TestCase
{
    private const string DEFAULT_METHOD = '__invoke';

    #[Test]
    public function itFailsToConstructWithNonExistingListeners(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not an existing class.');

        new HasListener('Not\A\Class');
    }

    #[Test]
    public function itFailsToConstructWithNonListenerMethods(): void
    {
        $method = 'thisIsNotTheMethodYouAreLookingFor';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("$method does not exist.");

        new HasListener(DummyListener::class, $method);
    }

    #[Test]
    public function itFailsToConstructWhenNotSpying(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(HasListener::class . '::spy()');

        new HasListener(DummyListener::class);
    }

    #[Test]
    public function itCanConstruct(): void
    {
        HasListener::spy();

        $instance = new HasListener(DummyListener::class);

        $this->assertSame(DummyListener::class, $instance->listener);
        $this->assertSame(self::DEFAULT_METHOD, $instance->method);
    }

    #[Test]
    public function itCanConstructWithListenerMethods(): void
    {
        HasListener::spy();
        $method = 'handle';

        $instance = new HasListener(DummyListener::class, $method);

        $this->assertSame(DummyListener::class, $instance->listener);
        $this->assertSame($method, $instance->method);
    }

    #[Test]
    public function itCanConstructFromListenerInstances(): void
    {
        HasListener::spy();
        $listener = new DummyListener();

        $instance = HasListener::instance($listener);

        $this->assertSame($listener::class, $instance->listener);
        $this->assertSame(self::DEFAULT_METHOD, $instance->method);
    }

    #[Test]
    #[TestWith([[]], 'Empty array')]
    #[TestWith([[DummyListener::class, 'handle']], 'Reference to non-static method')]
    public function itCannotConstructFromListenerMethodsThatAreNotCallable(array $listen): void
    {
        HasListener::spy();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not callable.');

        HasListener::method($listen);
    }

    #[Test]
    public function itCanConstructFromListenerMethods(): void
    {
        HasListener::spy();
        $listener = new DummyListener();

        $instance = HasListener::method([$listener, 'handle']);

        $this->assertSame($listener::class, $instance->listener);
        $this->assertSame('handle', $instance->method);
    }

    #[Test]
    public function itCannotEvaluateValuesThatAreNotStrings(): void
    {
        HasListener::spy();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            HasListener::class . ' can only be evaluated for strings',
        );

       $this->assertThat(new DummyEvent(), new HasListener(DummyListener::class));
    }

    #[Test]
    public function itFailsWhenItDoesNotHaveListeners(): void
    {
        HasListener::spy();

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('listener attached to it');

        $this->assertThat(DummyEvent::class, new HasListener(DummyListener::class));
    }

    #[Test]
    public function itPassesWhenItHasListeners(): void
    {
        HasListener::spy();

        Event::listen(DummyEvent::class, DummyListener::class);

        $this->assertThat(DummyEvent::class, new HasListener(DummyListener::class));
    }

    #[Test]
    public function itFailsWhenItDoesNotHaveListenersWithMethod(): void
    {
        HasListener::spy();
        Event::listen(DummyEvent::class, DummyListener::class);

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('listener attached to it');

        $this->assertThat(DummyEvent::class, new HasListener(DummyListener::class, 'handle'));
    }

    #[Test]
    public function itPassesWhenItHasListenersWithGivenMethod(): void
    {
        HasListener::spy();
        $method = 'handle';

        Event::listen(DummyEvent::class, [DummyListener::class, $method]);

        $this->assertThat(DummyEvent::class, new HasListener(DummyListener::class, $method));
    }
}
