<?php
declare(strict_types = 1);

namespace Innmind\CLI\Framework;

use Innmind\CLI\{
    Environment,
    Environment\ExitCode,
};
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

final class KeepVariablesInMemory implements Environment
{
    private Environment $env;
    /** @var Map<string, string>|null */
    private ?Map $variables = null;

    public function __construct(Environment $env)
    {
        $this->env = $env;
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
        return $this->variables ??= $this->env->variables();
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
