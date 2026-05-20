# Laravel Roster

<p align="center">
<a href="https://github.com/laravel/roster/actions"><img src="https://github.com/laravel/roster/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/roster"><img src="https://img.shields.io/packagist/dt/laravel/roster" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/roster"><img src="https://img.shields.io/packagist/v/laravel/roster" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/roster"><img src="https://img.shields.io/packagist/l/laravel/roster" alt="License"></a>
</p>

## Introduction

Laravel Roster is a detection package for the Laravel ecosystem. It reads your project's lockfiles, configuration markers, and (optionally) probes the host machine to answer questions about what is in use.

Roster exposes two surfaces. The `Project` facade reads your project's lockfiles and configuration markers to report installed packages, the application's stack, the frontend in use, browser test frameworks, configured AI agents, and the committed JS package manager. The `System` facade probes the host machine for AI agents and JS package manager binaries available on the `PATH`.

The two surfaces are kept separate because they have different lifecycles. Project scans are cheap and are keyed on a hash of your lockfile contents, while system probes shell out to the operating system and do not depend on your project state.

## Installation

You may install Roster as a development dependency via Composer:

```bash
composer require laravel/roster --dev
```

## Usage

Within a Laravel application, you may call the `Project` and `System` facades directly. The first call triggers a scan and the result is cached for subsequent calls:

```php
use Laravel\Roster\Enums\Agent;
use Laravel\Roster\Enums\Stack;
use Laravel\Roster\Facades\Project;
use Laravel\Roster\Facades\System;

Project::php()->uses('pestphp/pest');
Project::stack()->uses(Stack::INERTIA_REACT);
System::agents()->isInstalled(Agent::CURSOR);
```

Outside of a Laravel container, or when you would like an explicit handle, you may use the static `scan` methods:

```php
use Laravel\Roster\Project;
use Laravel\Roster\System;

$project = Project::scan();          // uses base_path() / getcwd()
$project = Project::scan($basePath);

$system = System::scan();
```

The examples that follow use `$project` and `$system` for clarity, but every call works on the corresponding facade.

### Packages

Packages are exposed through two ecosystems: `php()` for Composer and `js()` for npm, pnpm, yarn, and bun. Both ecosystems share the same surface:

```php
$ecosystem->uses(string|array $packages, ?string $constraint = null): bool
$ecosystem->usesAll(array $packages): bool
```

The `uses` method returns `true` when **any** of the given packages is present. The `usesAll` method returns `true` only when **every** package is present. Names are the raw package names you would write in `composer.json` or `package.json`.

The `$constraint` argument accepts any composer-semver string, such as `^1.2.3`, `~1.2`, `>=11 <14`, or `1.0 || ^2.0`. A bare version like `1.2.3` means an exact match. When omitted, only the package's presence is checked.

You may check a single package by name, optionally with a constraint:

```php
$project->php()->uses('pestphp/pest');
$project->php()->uses('laravel/framework', '^12.0');
$project->php()->uses('laravel/framework', '>=11 <14');
```

To check if **any** of several packages are present, pass an indexed array of names. Pass an associative array when you would like per-package constraints:

```php
$project->php()->uses(['pestphp/pest', 'phpunit/phpunit']);

$project->php()->uses([
    'pestphp/pest' => '^3.0',
    'phpunit/phpunit' => '^10.0',
]);
```

To require that **all** of several packages are present, use the `usesAll` method:

```php
$project->php()->usesAll(['pestphp/pest', 'laravel/framework']);

$project->php()->usesAll([
    'pestphp/pest' => '^3.0',
    'laravel/framework' => '^11.0',
]);
```

The JS ecosystem behaves the same way:

```php
$project->js()->uses('@inertiajs/react');
$project->js()->uses(['vue' => '^3.0', 'react' => '^18.0']);
$project->js()->usesAll(['vue', '@inertiajs/vue3']);
```

You may also retrieve the underlying `Package` instance or collection:

```php
$project->php()->package('pestphp/pest')?->version();
$project->js()->package('vue')?->major();
$project->php()->packages()->dev();
```

The array passed to `uses` and `usesAll` must be either entirely indexed (just names) or entirely associative (name to constraint). Mixing the two shapes throws an `InvalidArgumentException`.

### Stack, Frontend, and Browser Test Frameworks

The `stack`, `frontend`, and `browserTestFrameworks` methods on the `Project` surface return an `EnumSet` containing every detected case. You may invoke the `uses` method to check for membership, and the `all` method to retrieve every detected case:

```php
use Laravel\Roster\Enums\BrowserTestFramework;
use Laravel\Roster\Enums\Frontend;
use Laravel\Roster\Enums\Stack;

$project->stack()->uses(Stack::INERTIA_REACT);
$project->stack()->all();                              // Stack[]

$project->browserTestFrameworks()->uses(BrowserTestFramework::PLAYWRIGHT);
$project->browserTestFrameworks()->uses([
    BrowserTestFramework::PLAYWRIGHT,
    BrowserTestFramework::CYPRESS,
]);

$project->frontend()->uses(Frontend::REACT);
```

The `uses` method accepts either a single case or an array of cases. On `System` surfaces, use the `isInstalled` method instead.

### Agents and Editors

Agents (AI coding tools such as Claude Code, Cursor, and Codex) and editors (IDEs such as PHPStorm and VSCode) are exposed through separate enums. Each may be reported on the `Project` surface (detected through filesystem markers like `.claude`, `.cursor`, `.idea`, or `AGENTS.md`) or on the `System` surface (detected by looking for matching binaries on the `PATH`):

```php
use Laravel\Roster\Enums\Agent;
use Laravel\Roster\Enums\Editor;

$project->agents()->uses(Agent::CLAUDE_CODE);
$project->agents()->uses([Agent::CLAUDE_CODE, Agent::CURSOR]);
$project->editors()->uses(Editor::PHPSTORM);

$system->agents()->isInstalled(Agent::CURSOR);
$system->editors()->isInstalled(Editor::VSCODE);
```

### JS Package Managers

The `$project->js()->packageManager` method reports the package manager *committed* to the project as a single nullable enum, based on which lockfile is present (`package-lock.json`, `pnpm-lock.yaml`, and so on). The `$system->js()->packageManagers` method reports every package manager *installed* on the host as an `InstalledSet`:

```php
use Laravel\Roster\Enums\JsPackageManager;

$project->js()->packageManager()?->is(JsPackageManager::PNPM);

$system->js()->packageManagers()->isInstalled(JsPackageManager::BUN);
$system->js()->packageManagers()->all();
```

## Upgrading

See [UPGRADE.md](UPGRADE.md) for migrating from 0.x.

## Contributing

Thank you for considering contributing to Roster! The contribution guide may be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

Please review [our security policy](https://github.com/laravel/roster/security/policy) on how to report security vulnerabilities.

## License

Laravel Roster is open-sourced software licensed under the [MIT license](LICENSE.md).
