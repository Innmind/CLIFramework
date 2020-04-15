<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Framework;

use Innmind\CLI\Framework\DotEnvAware;
use Innmind\CLI\{
    Environment,
    Environment\ExitCode,
};
use Innmind\OperatingSystem\Filesystem;
use Innmind\Stream\{
    Readable,
    Writable,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};
use Fixtures\Innmind\Url\Path;
use Fixtures\Innmind\Immutable\Sequence;

class DotEnvAwareTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this
            ->forAll(Path::any())
            ->then(function($path) {
                $this->assertInstanceOf(
                    Environment::class,
                    new DotEnvAware(
                        $this->createMock(Environment::class),
                        $this->createMock(Filesystem::class),
                        $path,
                    ),
                );
            });
    }

    public function testInteractive()
    {
        $this
            ->forAll(
                Path::any(),
                Set\Elements::of(true, false),
            )
            ->then(function($path, $interactive) {
                $env = new DotEnvAware(
                    $inner = $this->createMock(Environment::class),
                    $this->createMock(Filesystem::class),
                    $path,
                );
                $inner
                    ->method('interactive')
                    ->willReturn($interactive);

                $this->assertSame($interactive, $env->interactive());
            });
    }

    public function testInput()
    {
        $this
            ->forAll(Path::any())
            ->then(function($path) {
                $env = new DotEnvAware(
                    $inner = $this->createMock(Environment::class),
                    $this->createMock(Filesystem::class),
                    $path,
                );
                $inner
                    ->method('input')
                    ->willReturn($input = $this->createMock(Readable::class));

                $this->assertSame($input, $env->input());
            });
    }

    public function testOutput()
    {
        $this
            ->forAll(Path::any())
            ->then(function($path) {
                $env = new DotEnvAware(
                    $inner = $this->createMock(Environment::class),
                    $this->createMock(Filesystem::class),
                    $path,
                );
                $inner
                    ->method('output')
                    ->willReturn($output = $this->createMock(Writable::class));

                $this->assertSame($output, $env->output());
            });
    }

    public function testError()
    {
        $this
            ->forAll(Path::any())
            ->then(function($path) {
                $env = new DotEnvAware(
                    $inner = $this->createMock(Environment::class),
                    $this->createMock(Filesystem::class),
                    $path,
                );
                $inner
                    ->method('error')
                    ->willReturn($error = $this->createMock(Writable::class));

                $this->assertSame($error, $env->error());
            });
    }

    public function testArguments()
    {
        $this
            ->forAll(
                Path::any(),
                Sequence::of('string', Set\Strings::any())
            )
            ->then(function($path, $arguments) {
                $env = new DotEnvAware(
                    $inner = $this->createMock(Environment::class),
                    $this->createMock(Filesystem::class),
                    $path,
                );
                $inner
                    ->method('arguments')
                    ->willReturn($arguments);

                $this->assertSame($arguments, $env->arguments());
            });
    }

    public function testExit()
    {
        $this
            ->forAll(
                Path::any(),
                Set\Integers::above(0),
            )
            ->then(function($path, $code) {
                $env = new DotEnvAware(
                    $inner = $this->createMock(Environment::class),
                    $this->createMock(Filesystem::class),
                    $path,
                );
                $inner
                    ->expects($this->once())
                    ->method('exit')
                    ->with($code);

                $this->assertNull($env->exit($code));
            });
    }

    public function testExitCode()
    {
        $this
            ->forAll(
                Path::any(),
                Set\Integers::above(0),
            )
            ->then(function($path, $code) {
                $env = new DotEnvAware(
                    $inner = $this->createMock(Environment::class),
                    $this->createMock(Filesystem::class),
                    $path,
                );
                $inner
                    ->method('exitCode')
                    ->willReturn($expected = new ExitCode($code));

                $this->assertSame($expected, $env->exitCode());
            });
    }

    public function testWorkingDirectory()
    {
        $this
            ->forAll(
                Path::any(),
                Path::any(),
            )
            ->then(function($path, $workingDirectory) {
                $env = new DotEnvAware(
                    $inner = $this->createMock(Environment::class),
                    $this->createMock(Filesystem::class),
                    $path,
                );
                $inner
                    ->method('workingDirectory')
                    ->willReturn($workingDirectory);

                $this->assertSame($workingDirectory, $env->workingDirectory());
            });
    }
}
