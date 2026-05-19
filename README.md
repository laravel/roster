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
$ecosystem->uses(string $name, ?string $version = null, string $operator = '>='): bool
```

`$name` is either the alias (`pest`, `inertia-react`) or the raw package
name (`pestphp/pest`, `@inertiajs/react`). Pass `$version` to also do a
SemVer comparison — when omitted, it's a pure presence check. The
operator defaults to `>=`, which is what you almost always want.

```php
$project->php()->uses('pest');                          // alias
$project->php()->uses('pestphp/pest');                  // raw
$project->php()->uses('framework', '12.0.0');           // present and >= 12.0.0
$project->php()->uses('framework', '12.0.0', '<');      // present and < 12.0.0
$project->php()->package('pest')?->version();
$project->php()->packages()->dev();

$project->js()->uses('vue');
$project->js()->uses('@inertiajs/react');
$project->js()->uses('vue', '3.0.0');
$project->js()->package('vue')?->major();
```

### Aliases

Every package can always be queried by its raw name (`laravel/pint`,
`@inertiajs/react`). On top of that, packages from the Laravel-ecosystem
namespaces get a short alias automatically so you can write
`$project->php()->uses('pint')` instead of the full vendor-qualified string.

The rules:

| Source   | Namespace      | Rule                                                                       | Example                                                       |
|----------|----------------|----------------------------------------------------------------------------|---------------------------------------------------------------|
| composer | `laravel/`     | strip vendor                                                               | `laravel/pint` → `pint`                                       |
| composer | `inertiajs/`   | strip vendor; prepend `inertia-` if the result doesn't already start with `inertia` | `inertiajs/inertia-laravel` → `inertia-laravel`              |
| composer | `pestphp/`     | strip vendor; prepend `pest-` if the result doesn't already start with `pest`       | `pestphp/pest` → `pest`, `pestphp/plugin-browser` → `pest-plugin-browser` |
| npm      | `@inertiajs/`  | strip scope; prepend `inertia-` if the result doesn't already start with `inertia` | `@inertiajs/react` → `inertia-react`                          |

Packages outside these namespaces have **no alias** — they are queryable only
by raw name. Other npm scopes (`@vue/`, `@sveltejs/`, `@tanstack/`, etc.) are
deliberately not auto-aliased because their stripped names (`compiler-sfc`,
`kit`, `react-query`) are tool-internal and not useful as concept aliases.

To register your own alias for a package, use `Project::extend()`. Explicit
aliases always win over the auto-alias rules:

```php
use Laravel\Roster\Facades\Project;
use Laravel\Roster\Registry;

Project::extend(function (Registry $r) {
    $r->php('spatie/laravel-permission', alias: 'permission');
    $r->js('@tanstack/react-query', alias: 'react-query');
});

// Then either form works:
$project->php()->uses('permission');
$project->php()->uses('spatie/laravel-permission');
```

### Stack, test framework, frontend

```php
use Laravel\Roster\Enums\BrowserTestFramework;
use Laravel\Roster\Enums\Frontend;
use Laravel\Roster\Enums\Stack;
use Laravel\Roster\Enums\TestFramework;

$project->stack()->uses(Stack::INERTIA_REACT);
$project->stack()->all();                              // Stack[]

$project->testFramework()?->is(TestFramework::PEST);
$project->browserTestFrameworks()->uses(BrowserTestFramework::PLAYWRIGHT);
$project->browserTestFrameworks()->uses([
    BrowserTestFramework::PLAYWRIGHT,
    BrowserTestFramework::CYPRESS,
]);

$project->frontend()->uses(Frontend::REACT);
```

`uses()` accepts either a single case or an array of cases. On `System`
surfaces, use `isInstalled()` instead.

### Starter kit

```php
use Laravel\Roster\Enums\StarterKit;

$project->starterKit()?->is(StarterKit::REACT);
$project->starterKit();                                // ?StarterKit (null when none match)
```

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
as a single `?JsPackageManager`. `System::packageManagers()` reports the
managers *installed* on the host as a set.

```php
use Laravel\Roster\Enums\JsPackageManager;

$project->js()->packageManager()?->is(JsPackageManager::PNPM);
$system->packageManagers()->isInstalled(JsPackageManager::BUN);
$system->packageManagers()->all();
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
