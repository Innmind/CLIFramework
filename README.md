# CLI Framework

[![codecov](https://codecov.io/gh/Innmind/CLIFramework/branch/develop/graph/badge.svg)](https://codecov.io/gh/Innmind/CLIFramework)
[![Build Status](https://github.com/Innmind/CLIFramework/workflows/CI/badge.svg?branch=master)](https://github.com/Innmind/CLIFramework/actions?query=workflow%3ACI)
[![Type Coverage](https://shepherd.dev/github/Innmind/CLIFramework/coverage.svg)](https://shepherd.dev/github/Innmind/CLIFramework)

Small library on top of [`innmind/cli`](https://github.com/innmind/cli) to automatically enable some features.

## Installation

```sh
composer require innmind/cli-framework
```

## Usage

```php
<?php

use Innmind\CLI\{
    Environment,
    Command,
};
use Innmind\CLI\Framework\{
    Application,
    Main,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Url\Path;

new class extends Main {
    protected function configure(Application $app): Application
    {
        return $app
            ->configAt(Path::of('/path/to/config/directory/'))
            ->commands(fn(Environment $env, OperatingSystem $os): array => [
                // a list of objects implementing Command
            ]);
    }
}
```

This simple example will try to locate a file named `.env` in the directory provided and will add the variables to the map returned by `$env->variables()` in the `commands` callable.

By default it enables the usage of [`innmind/silent-cartographer`](https://github.com/innmind/silentcartographer), but can be disabled by calling `->disableSilentCartographer()`.

When a `PROFILER` environment variable is declared it will enable [`innmind/debug`](https://github.com/innmind/debug), you can disable specific sections of the profiler by calling `->disableProfilerSection(...$sectionsClassNameToDisable)`.

When your CLI application communicate with external services you should call `->useResilientOperatingSystem()` so it accomodate inconsistencies due to unreliable network.
