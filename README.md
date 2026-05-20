# Laravel Roster

<p align="center">
<a href="https://github.com/laravel/roster/actions"><img src="https://github.com/laravel/roster/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/roster"><img src="https://img.shields.io/packagist/dt/laravel/roster" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/roster"><img src="https://img.shields.io/packagist/v/laravel/roster" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/roster"><img src="https://img.shields.io/packagist/l/laravel/roster" alt="License"></a>
</p>

## Introduction

Laravel Roster is the detection package for the Laravel ecosystem. It exposes
two surfaces:

- **`Project`** — reads your project's lockfiles and configuration markers to
  answer questions about what's in use: packages, stacks, frontends, test
  frameworks, starter kits, configured agents, and committed JS package
  managers.
- **`System`** — probes the host machine for installed AI/editor agents and JS
  package manager binaries on `PATH`.

The two are split because they have different lifecycles and different cost:
project scans are cheap and key on lockfile hashes; system probes shell out to
the OS and don't depend on your project state.

## Installation

```bash
composer require laravel/roster --dev
```

## Usage

In a Laravel app you can call either facade directly — the first call triggers
a scan and the result is cached for subsequent calls:

```php
use Laravel\Roster\Facades\Project;
use Laravel\Roster\Facades\System;
use Laravel\Roster\Enums\Stack;
use Laravel\Roster\Enums\Agent;

Project::php()->uses('pest');
Project::stack()->uses(Stack::INERTIA_REACT);
System::agents()->isInstalled(Agent::CURSOR);
```

Outside a Laravel container, or when you want an explicit handle, use the
static `scan()` entry points:

```php
use Laravel\Roster\Project;
use Laravel\Roster\System;

$project = Project::scan();          // uses base_path() / getcwd()
$project = Project::scan($basePath);

$system = System::scan();
```

The examples below use `$project` / `$system` for clarity, but every call
works on the facade too.

### Packages — split by ecosystem

Packages live under two ecosystems: `php()` (composer) and `js()` (npm /
pnpm / yarn / bun). Both expose the same surface:

```php
$ecosystem->uses(string|array $packages, ?string $constraint = null): bool
$ecosystem->usesAll(array $packages): bool
```

`uses()` returns true if **any** of the given packages is present.
`usesAll()` returns true only if **every** package is present. Names are
raw package names (`pestphp/pest`, `@inertiajs/react`, `vue`).
`$constraint` is any composer-semver string (`^1.2.3`, `~1.2`, `>=11 <14`,
`1.0 || ^2.0`, bare `1.2.3` = exact match). When omitted, it's a presence
check only.

```php
// single
$project->php()->uses('pestphp/pest');
$project->php()->uses('laravel/framework', '^12.0');     // present and satisfies ^12.0
$project->php()->uses('laravel/framework', '>=11 <14');  // composite range

// any-of (indexed array = no constraints)
$project->php()->uses(['pestphp/pest', 'phpunit/phpunit']);

// any-of (assoc array = per-package constraints, composer.json style)
$project->php()->uses([
    'pestphp/pest' => '^3.0',
    'phpunit/phpunit' => '^10.0',
]);

// all-of
$project->php()->usesAll(['pestphp/pest', 'laravel/framework']);
$project->php()->usesAll([
    'pestphp/pest' => '^3.0',
    'laravel/framework' => '^11.0',
]);

// JS works the same
$project->js()->uses('@inertiajs/react');
$project->js()->uses(['vue' => '^3.0', 'react' => '^18.0']);
$project->js()->usesAll(['vue', '@inertiajs/vue3']);

// Package lookups
$project->php()->package('pestphp/pest')?->version();
$project->js()->package('vue')?->major();
$project->php()->packages()->dev();
```

The array must be either all-indexed (just names) or all-assoc (name =>
constraint). Mixing the two shapes throws `InvalidArgumentException`.

### Stack, frontend, browser test frameworks

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

`uses()` accepts either a single case or an array of cases. On `System`
surfaces, use `isInstalled()` instead.

### Agents

`Project::agents()` reports agents *configured* in the repo (filesystem
markers like `.claude`, `.cursor`, `AGENTS.md`). `System::agents()` reports
agents *installed* on the host (binaries on `PATH`).

```php
use Laravel\Roster\Enums\Agent;

$project->agents()->uses(Agent::CLAUDE_CODE);          // marker file present
$project->agents()->uses([Agent::CLAUDE_CODE, Agent::CURSOR]);
$project->agents()->all();                             // Agent[]

$system->agents()->isInstalled(Agent::CURSOR);         // `cursor` on PATH
$system->agents()->all();
```

### JS package managers

`$project->js()->packageManager()` reports the package manager *committed*
to the project (presence of `package-lock.json` / `pnpm-lock.yaml` / etc.)
as a single `?JsPackageManager`. `$system->js()->packageManagers()` reports
the managers *installed* on the host as a set.

```php
use Laravel\Roster\Enums\JsPackageManager;

$project->js()->packageManager()?->is(JsPackageManager::PNPM);
$system->js()->packageManagers()->isInstalled(JsPackageManager::BUN);
$system->js()->packageManagers()->all();
```

## Upgrading

See [UPGRADE.md](UPGRADE.md) for migrating from 0.x.

## Contributing

Thank you for considering contributing to Roster! The contribution guide can be found in
the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by
the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

Please review [our security policy](https://github.com/laravel/roster/security/policy) on how to report security
vulnerabilities.

## License

Laravel Roster is open-sourced software licensed under the [MIT license](LICENSE.md).
