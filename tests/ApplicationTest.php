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
use Innmind\OperatingSystem\{
    OperatingSystem,
    Filesystem,
    CurrentProcess,
};
use Innmind\Filesystem\{
    Adapter\InMemory,
    File\File,
};
use Innmind\Server\Status\Server;
use Innmind\Server\Control\Server\Process\Pid;
use Innmind\SilentCartographer\OperatingSystem as SilentCartographer;
use Innmind\Stream\Readable\Stream;
use Innmind\Url\Path;
use Innmind\Stream\Writable;
use Innmind\Immutable\{
    Str,
    Sequence,
    Map,
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
        $os = $this->createMock(OperatingSystem::class);

        $app = Application::of($env, $os);
        $app = $app->disableSilentCartographer();

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
        $os = $this->createMock(OperatingSystem::class);

        $app = Application::of($env, $os);
        $app = $app->disableSilentCartographer();
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
        $os = $this->createMock(OperatingSystem::class);

        $app = Application::of($env, $os);
        $app = $app->disableSilentCartographer();
        $app2 = $app->commands(fn() => [$foo, $bar]);

        $this->assertInstanceOf(Application::class, $app2);
        $this->assertNotSame($app, $app2);
        $this->assertNull($app2->run());
    }

    public function testNoErrorWhenSpecifyingUnknownConfigDirectory()
    {
        $configPath = Path::of('/somewhere/');
        $env = $this->createMock(Environment::class);
        $env
            ->method('arguments')
            ->willReturn(Sequence::strings());
        $os = $this->createMock(OperatingSystem::class);
        $os
            ->method('filesystem')
            ->willReturn($filesystem = $this->createMock(Filesystem::class));
        $filesystem
            ->method('contains')
            ->with($configPath)
            ->willReturn(false);
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

        $app = Application::of($env, $os);
        $app2 = $app
            ->disableSilentCartographer()
            ->commands(fn() => [$command])
            ->configAt($configPath);

        $this->assertInstanceOf(Application::class, $app2);
        $this->assertNotSame($app, $app2);
        $this->assertNull($app2->run());
    }

    public function testNoErrorWhenSpecifyingConfigDirectoryWithoutADotEnvFile()
    {
        $configPath = Path::of('/somewhere/');
        $env = $this->createMock(Environment::class);
        $env
            ->method('arguments')
            ->willReturn(Sequence::strings());
        $os = $this->createMock(OperatingSystem::class);
        $os
            ->method('filesystem')
            ->willReturn($filesystem = $this->createMock(Filesystem::class));
        $filesystem
            ->method('contains')
            ->with($configPath)
            ->willReturn(true);
        $filesystem
            ->method('mount')
            ->with($configPath)
            ->willReturn(new InMemory);
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

        $app = Application::of($env, $os);
        $app2 = $app
            ->disableSilentCartographer()
            ->commands(fn() => [$command])
            ->configAt($configPath);

        $this->assertInstanceOf(Application::class, $app2);
        $this->assertNotSame($app, $app2);
        $this->assertNull($app2->run());
    }

    public function testLoadDotEnv()
    {
        $configPath = Path::of('/somewhere/');
        $env = $this->createMock(Environment::class);
        $env
            ->method('arguments')
            ->willReturn(Sequence::strings());
        $env
            ->method('variables')
            ->willReturn(Map::of('string', 'string'));
        $os = $this->createMock(OperatingSystem::class);
        $os
            ->method('filesystem')
            ->willReturn($filesystem = $this->createMock(Filesystem::class));
        $filesystem
            ->method('contains')
            ->with($configPath)
            ->willReturn(true);
        $filesystem
            ->method('mount')
            ->with($configPath)
            ->willReturn($config = new InMemory);
        $config->add(File::named(
            '.env',
            Stream::ofContent('FOO=bar'),
        ));
        $command = new class implements Command {
            public function __invoke(Environment $env, Arguments $arguments, Options $options): void
            {
                if (!$env->variables()->contains('FOO')) {
                    throw new \Exception('Dot env not loaded');
                }
            }

            public function toString(): string
            {
                return 'foo';
            }
        };

        $app = Application::of($env, $os);
        $app2 = $app
            ->disableSilentCartographer()
            ->commands(function($env) use ($command) {
                if (!$env->variables()->contains('FOO')) {
                    $this->fail('Dot env not loaded');
                }

                return [$command];
            })
            ->configAt($configPath);

        $this->assertInstanceOf(Application::class, $app2);
        $this->assertNotSame($app, $app2);
        $this->assertNull($app2->run());
    }

    public function testSilentCartographerEnabledByDefaultWithWorkingDirectoryAsRoomLocation()
    {
        $env = $this->createMock(Environment::class);
        $env
            ->method('arguments')
            ->willReturn(Sequence::strings());
        $env
            ->method('workingDirectory')
            ->willReturn(Path::of('/working/directory/'));
        $env
            ->method('output')
            ->willReturn($output = $this->createMock(Writable::class));
        $output
            ->expects($this->once())
            ->method('write')
            ->with(Str::of("foo"));
        $os = $this->createMock(OperatingSystem::class);
        $os
            ->method('process')
            ->willReturn($process = $this->createMock(CurrentProcess::class));
        $process
            ->method('id')
            ->willReturn(new Pid(42));
        $os
            ->method('status')
            ->willReturn($status = $this->createMock(Server::class));
        $status
            ->method('tmp')
            ->willReturn(Path::of('/tmp/'));

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

        $app = Application::of($env, $os);
        $app = $app->commands(function($env, $os) use ($command) {
            if (!$os instanceof SilentCartographer) {
                $this->fail('Silent cartographer not enabled');
            }

            return [$command];
        });

        $this->assertNull($app->run());
    }
}
