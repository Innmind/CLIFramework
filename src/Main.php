<?php
declare(strict_types = 1);

namespace Innmind\CLI\Framework;

use Innmind\CLI\{
    Main as CLI,
    Environment,
};
use Innmind\OperatingSystem\OperatingSystem;

abstract class Main extends CLI
{
    protected function main(Environment $env, OperatingSystem $os): void
    {
        $this
            ->configure(Application::of($env, $os))
            ->run();
    }

    abstract protected function configure(Application $app): Application;
}
