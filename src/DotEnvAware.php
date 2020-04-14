<?php
declare(strict_types = 1);

namespace Innmind\CLI\Framework;

use Innmind\CLI\{
    Environment,
    Environment\ExitCode,
};
use Innmind\OperatingSystem\Filesystem;
use Innmind\Filesystem\Name;
use Innmind\Url\Path;
use Innmind\Stream\{
    Readable,
    Writable,
};
use Innmind\Immutable\{
    Map,
    Sequence,
};
use Symfony\Component\Dotenv\Dotenv;

final class DotEnvAware implements Environment
{
    private Environment $env;
    private Filesystem $filesystem;
    private Path $config;

    public function __construct(
        Environment $env,
        Filesystem $filesystem,
        Path $config
    ) {
        $this->env = $env;
        $this->filesystem = $filesystem;
        $this->config = $config;
    }

    public function interactive(): bool
    {
        return $this->env->interactive();
    }

    public function input(): Readable
    {
        return $this->env->input();
    }

    public function output(): Writable
    {
        return $this->env->output();
    }

    public function error(): Writable
    {
        return $this->env->error();
    }

    public function arguments(): Sequence
    {
        return $this->env->arguments();
    }

    public function variables(): Map
    {
        $variables = $this->env->variables();

        if (!$this->filesystem->contains($this->config)) {
            return $variables;
        }

        $config = $this->filesystem->mount($this->config);

        if (!$config->contains(new Name('.env'))) {
            return $variables;
        }

        /** @var array<string, string> */
        $dot = (new Dotenv)->parse($config->get(new Name('.env'))->content()->toString());

        foreach ($dot as $key => $value) {
            $variables = ($variables)($key, $value);
        }

        return $variables;
    }

    public function exit(int $code): void
    {
        $this->env->exit($code);
    }

    public function exitCode(): ExitCode
    {
        return $this->env->exitCode();
    }

    public function workingDirectory(): Path
    {
        return $this->env->workingDirectory();
    }
}
