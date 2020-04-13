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

    /**
     * @param callable(Environment, OperatingSystem): list<Command> $commands
     */
    private function __construct(
        Environment $env,
        OperatingSystem $os,
        callable $commands
    ) {
        $this->env = $env;
        $this->os = $os;
        $this->commands = \Closure::fromCallable($commands);
    }

    public static function of(Environment $env, OperatingSystem $os): self
    {
        return new self(
            $env,
            $os,
            static fn(): array => [],
        );
    }

    /**
     * @param callable(Environment, OperatingSystem): list<Command> $commands
     */
    public function commands(callable $commands): self
    {
        return new self(
            $this->env,
            $this->os,
            fn(Environment $env, OperatingSystem $os): array => \array_merge(
                ($this->commands)($env, $os),
                $commands($env, $os),
            ),
        );
    }

    public function run(): void
    {
        $commands = ($this->commands)($this->env, $this->os);
        $commands = \count($commands) === 0 ? [new HelloWorld] : $commands;

        $run = new Commands(...$commands);
        $run($this->env);
    }
}
