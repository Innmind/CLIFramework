<?php
declare(strict_types = 1);

namespace Innmind\CLI\Framework;

use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\Immutable\Str;

final class HelloWorld implements Command
{
    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        $env->output()->write(Str::of("Hello world\n"));
    }

    public function toString(): string
    {
        return 'hello-world';
    }
}
