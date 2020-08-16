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
use Innmind\Immutable\{
    Set,
    Map,
};
use function Innmind\SilentCartographer\bootstrap as cartographer;
use function Innmind\Debug\bootstrap as debug;
use function Innmind\Stack\stack;
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
    /** @var \Closure(OperatingSystem): OperatingSystem */
    private \Closure $useResilientOperatingSystem;
    /** @var list<class-string<Section>> */
    private array $disabledSections;

    /**
     * @param callable(Environment, OperatingSystem): list<Command> $commands
     * @param callable(Environment, OperatingSystem): Environment $loadDotEnv
     * @param callable(Environment, OperatingSystem): OperatingSystem $enableSilentCartographer
     * @param callable(OperatingSystem): OperatingSystem $useResilientOperatingSystem
     * @param list<class-string<Section>> $disabledSections
     */
    private function __construct(
        Environment $env,
        OperatingSystem $os,
        callable $commands,
        callable $loadDotEnv,
        callable $enableSilentCartographer,
        callable $useResilientOperatingSystem,
        array $disabledSections
    ) {
        $this->env = $env;
        $this->os = $os;
        $this->commands = \Closure::fromCallable($commands);
        $this->loadDotEnv = \Closure::fromCallable($loadDotEnv);
        $this->enableSilentCartographer = \Closure::fromCallable($enableSilentCartographer);
        $this->useResilientOperatingSystem = \Closure::fromCallable($useResilientOperatingSystem);
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
            static fn(OperatingSystem $os): OperatingSystem => $os,
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
            $this->useResilientOperatingSystem,
            $this->disabledSections,
        );
    }

    public function configAt(Path $path): self
    {
        return new self(
            $this->env,
            $this->os,
            $this->commands,
            static fn(Environment $env, OperatingSystem $os): Environment => new KeepVariablesInMemory(
                new DotEnvAware(
                    $env,
                    $os->filesystem(),
                    $path,
                ),
            ),
            $this->enableSilentCartographer,
            $this->useResilientOperatingSystem,
            $this->disabledSections,
        );
    }

    public function disableSilentCartographer(): self
    {
        return new self(
            $this->env,
            $this->os,
            $this->container,
            $this->commands,
            $this->loadDotEnv,
            static fn(Environment $env, OperatingSystem $os): OperatingSystem => $os,
            $this->useResilientOperatingSystem,
            $this->disabledSections,
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
            $this->useResilientOperatingSystem,
            \array_merge(
                $this->disabledSections,
                $sections,
            ),
        );
    }

    public function useResilientOperatingSystem(): self
    {
        return new self(
            $this->env,
            $this->os,
            $this->commands,
            $this->loadDotEnv,
            $this->enableSilentCartographer,
            static fn(OperatingSystem $os): OperatingSystem => new OperatingSystem\Resilient($os),
            $this->disabledSections,
        );
    }

    public function run(): void
    {
        $os = ($this->enableSilentCartographer)($this->env, $this->os);
        // done after the silent cartographer so that retries show up in the
        // cartographer panel
        $os = ($this->useResilientOperatingSystem)($os);
        $env = ($this->loadDotEnv)($this->env, $os);
        $debugEnabled = $env->variables()->contains('PROFILER');
        $middlewares = [static fn(array $commands): array => $commands];

        if ($debugEnabled) {
            /** @var Map<string, scalar> */
            $variables = $env->variables()->toMapOf('string', 'scalar');
            $debug = debug(
                $os,
                Url::of($env->variables()->get('PROFILER')),
                $variables,
                null,
                Set::strings(...$this->disabledSections),
            );
            $os = $debug['os']();
            /** @psalm-suppress MixedArgument */
            $middlewares[] = static fn(array $commands): array => unwrap(
                $debug['cli'](...$commands),
            );
        }

        $commands = ($this->commands)($env, $os);
        $commands = \count($commands) === 0 ? [new HelloWorld] : $commands;

        /** @var list<Command> */
        $commands = stack(...$middlewares)($commands);

        $run = new Commands(...$commands);
        $run($env);
    }
}
