<?php
declare(strict_types = 1);

namespace Innmind\CLI\Framework;

use Innmind\CLI\{
    Environment,
    Command,
    Commands,
};
use Innmind\OperatingSystem\OperatingSystem;

final class Application
{
    private Environment $env;
    private OperatingSystem $os;
    /** @var \Closure(Environment, OperatingSystem): list<Command> */
    private \Closure $commands;

    public function __construct(Environment $env, OperatingSystem $os)
    {
        $this->env = $env;
        $this->os = $os;
        $this->commands = static fn(): array => [new HelloWorld];
    }

    public function run(): void
    {
        $run = new Commands(...($this->commands)(
            $this->env,
            $this->os,
        ));

        $run($this->env);
    }
}
