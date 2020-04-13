<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Framework;

use Innmind\CLI\Framework\Application;
use Innmind\CLI\{
    Environment,
    Command,
    Command\Arguments,
    Command\Options,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Stream\Writable;
use Innmind\Immutable\{
    Str,
    Sequence,
};
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    public function testRunAHelloWorldByDefault()
    {
        $env = $this->createMock(Environment::class);
        $env
            ->method('arguments')
            ->willReturn(Sequence::strings());
        $env
            ->method('output')
            ->willReturn($output = $this->createMock(Writable::class));
        $output
            ->expects($this->once())
            ->method('write')
            ->with(Str::of("Hello world\n"));

        $app = Application::of(
            $env,
            $this->createMock(OperatingSystem::class),
        );

        $this->assertNull($app->run());
    }

    public function testHelloWorldCommandDisappearWhenOneCommandProvided()
    {
        $command = new class implements Command {
            public function __invoke(Environment $env, Arguments $arguments, Options $options): void
            {
                $env->output()->write(Str::of('foo'));
            }

            public function toString(): string
            {
                return 'foo';
            }
        };

        $env = $this->createMock(Environment::class);
        $env
            ->method('arguments')
            ->willReturn(Sequence::strings());
        $env
            ->method('output')
            ->willReturn($output = $this->createMock(Writable::class));
        $output
            ->expects($this->once())
            ->method('write')
            ->with($this->callback(function($text) {
                return !$text->contains('Hello world') &&
                    $text->contains('foo');
            }));

        $app = Application::of(
            $env,
            $this->createMock(OperatingSystem::class),
        );
        $app2 = $app->commands(fn() => [$command]);

        $this->assertInstanceOf(Application::class, $app2);
        $this->assertNotSame($app, $app2);
        $this->assertNull($app2->run());
    }

    public function testListCommandsWhenMoreThanOneProvided()
    {
        $foo = new class implements Command {
            public function __invoke(Environment $env, Arguments $arguments, Options $options): void
            {
            }

            public function toString(): string
            {
                return 'foo';
            }
        };
        $bar = new class implements Command {
            public function __invoke(Environment $env, Arguments $arguments, Options $options): void
            {
            }

            public function toString(): string
            {
                return 'bar';
            }
        };

        $env = $this->createMock(Environment::class);
        $env
            ->method('arguments')
            ->willReturn(Sequence::strings());
        $env
            ->method('output')
            ->willReturn($output = $this->createMock(Writable::class));
        $output
            ->method('write')
            ->with($this->callback(function($text) {
                return !$text->contains('Hello world') &&
                    ($text->contains('foo') || $text->contains('bar'));
            }));

        $app = Application::of(
            $env,
            $this->createMock(OperatingSystem::class),
        );
        $app2 = $app->commands(fn() => [$foo, $bar]);

        $this->assertInstanceOf(Application::class, $app2);
        $this->assertNotSame($app, $app2);
        $this->assertNull($app2->run());
    }
}
