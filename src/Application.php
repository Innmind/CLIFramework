<?php
declare(strict_types = 1);

namespace Innmind\CLI\Framework;

use Innmind\CLI\{
    Environment,
    Command,
    Commands,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Url\Path;

final class Application
{
    private Environment $env;
    private OperatingSystem $os;
    /** @var \Closure(Environment, OperatingSystem): list<Command> */
    private \Closure $commands;
    /** @var \Closure(Environment, OperatingSystem): Environment */
    private \Closure $loadDotEnv;

    /**
     * @param callable(Environment, OperatingSystem): list<Command> $commands
     * @param callable(Environment, OperatingSystem): Environment $loadDotEnv
     */
    private function __construct(
        Environment $env,
        OperatingSystem $os,
        callable $commands,
        callable $loadDotEnv
    ) {
        $this->env = $env;
        $this->os = $os;
        $this->commands = \Closure::fromCallable($commands);
        $this->loadDotEnv = \Closure::fromCallable($loadDotEnv);
    }

    public static function of(Environment $env, OperatingSystem $os): self
    {
        return new self(
            $env,
            $os,
            static fn(): array => [],
            static fn(Environment $env): Environment => $env,
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
            $this->loadDotEnv,
        );
    }

    public function configAt(Path $path): self
    {
        return new self(
            $this->env,
            $this->os,
            $this->commands,
            static fn(Environment $env, OperatingSystem $os): Environment => new DotEnvAware(
                $env,
                $os->filesystem(),
                $path,
            ),
        );
    }

    public function run(): void
    {
        $env = ($this->loadDotEnv)($this->env, $this->os);
        $commands = ($this->commands)($env, $this->os);
        $commands = \count($commands) === 0 ? [new HelloWorld] : $commands;

        $run = new Commands(...$commands);
        $run($env);
    }
}
