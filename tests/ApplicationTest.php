<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Framework;

use Innmind\CLI\Framework\Application;
use Innmind\CLI\Environment;
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

        $app = new Application(
            $env,
            $this->createMock(OperatingSystem::class),
        );

        $this->assertNull($app->run());
    }
}
