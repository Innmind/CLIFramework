<?php
declare(strict_types = 1);

namespace Innmind\CLI\Framework;

use Innmind\CLI\{
    Environment,
    Command,
    Commands,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Debug\Profiler\Section;
use Innmind\Url\{
    Path,
    Url,
};
use Innmind\Immutable\Set;
use function Innmind\SilentCartographer\bootstrap as cartographer;
use function Innmind\Debug\bootstrap as debug;
use function Innmind\Immutable\unwrap;

final class Application
{
    private Environment $env;
    private OperatingSystem $os;
    /** @var \Closure(Environment, OperatingSystem): list<Command> */
    private \Closure $commands;
    /** @var \Closure(Environment, OperatingSystem): Environment */
    private \Closure $loadDotEnv;
    /** @var \Closure(Environment, OperatingSystem): OperatingSystem */
    private \Closure $enableSilentCartographer;
    /** @var list<class-string<Section>> */
    private array $disabledSections;

    /**
     * @param callable(Environment, OperatingSystem): list<Command> $commands
     * @param callable(Environment, OperatingSystem): Environment $loadDotEnv
     * @param callable(Environment, OperatingSystem): OperatingSystem $enableSilentCartographer
     * @param list<class-string<Section>> $disabledSections
     */
    private function __construct(
        Environment $env,
        OperatingSystem $os,
        callable $commands,
        callable $loadDotEnv,
        callable $enableSilentCartographer,
        array $disabledSections
    ) {
        $this->env = $env;
        $this->os = $os;
        $this->commands = \Closure::fromCallable($commands);
        $this->loadDotEnv = \Closure::fromCallable($loadDotEnv);
        $this->enableSilentCartographer = \Closure::fromCallable($enableSilentCartographer);
        $this->disabledSections = $disabledSections;
    }

    public static function of(Environment $env, OperatingSystem $os): self
    {
        return new self(
            $env,
            $os,
            static fn(): array => [],
            static fn(Environment $env): Environment => $env,
            static fn(Environment $env, OperatingSystem $os): OperatingSystem => cartographer($os)['cli'](
                Url::of('/')->withPath(
                    $env->workingDirectory(),
                ),
            ),
            [],
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
            $this->enableSilentCartographer,
            [],
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
            $this->enableSilentCartographer,
            [],
        );
    }

    public function disableSilentCartographer(): self
    {
        return new self(
            $this->env,
            $this->os,
            $this->commands,
            $this->loadDotEnv,
            static fn(Environment $env, OperatingSystem $os): OperatingSystem => $os,
            [],
        );
    }

    /**
     * @param list<class-string<Section>> $sections
     */
    public function disableProfilerSection(string ...$sections): self
    {
        return new self(
            $this->env,
            $this->os,
            $this->commands,
            $this->loadDotEnv,
            $this->enableSilentCartographer,
            \array_merge(
                $this->disabledSections,
                $sections,
            ),
        );
    }

    public function run(): void
    {
        $os = ($this->enableSilentCartographer)($this->env, $this->os);
        $env = ($this->loadDotEnv)($this->env, $os);
        $debugEnabled = $env->variables()->contains('PROFILER');
        $wrapCommands = static fn(Command ...$commands): array => $commands;

        if ($debugEnabled) {
            $debug = debug(
                $os,
                Url::of($env->variables()->get('PROFILER')),
                $env->variables(),
                null,
                Set::strings(...$this->disabledSections),
            );
            $os = $debug['os']();
            $wrapCommands = static fn(Command ...$commands): array => unwrap(
                $debug['cli'](...$commands),
            );
        }

        $commands = ($this->commands)($env, $os);
        $commands = \count($commands) === 0 ? [new HelloWorld] : $commands;

        if ($debugEnabled) {
            $commands = $wrapCommands(...$commands);
        }

        $run = new Commands(...$commands);
        $run($env);
    }
}
