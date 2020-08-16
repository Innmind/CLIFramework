<?php
declare(strict_types = 1);

namespace Innmind\CLI\Framework;

use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\DI\ServiceLocator;

final class DeferCommand implements Command
{
    private ServiceLocator $get;
    private string $name;

    public function __construct(ServiceLocator $get, string $name)
    {
        $this->get = $get;
        $this->name = $name;
    }

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        $this->command()($env, $arguments, $options);
    }

    public function toString(): string
    {
        return $this->command()->toString();
    }

    private function command(): Command
    {
        /** @var Command */
        return ($this->get)($this->name);
    }
}
