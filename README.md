# Laravel Roster

<p align="center">
<a href="https://github.com/laravel/roster/actions"><img src="https://github.com/laravel/roster/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/roster"><img src="https://img.shields.io/packagist/dt/laravel/roster" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/roster"><img src="https://img.shields.io/packagist/v/laravel/roster" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/roster"><img src="https://img.shields.io/packagist/l/laravel/roster" alt="License"></a>
</p>

## Introduction

Laravel Roster is the detection package for the Laravel ecosystem. It reads
your project's lockfiles, configuration markers, and (optionally) machine-level
signals to answer questions about what is in use — packages, stacks, frontends,
test frameworks, starter kits, and AI agents / editors.

## Installation

```bash
composer require laravel/roster --dev
```

## Usage

In a Laravel app you can use the `Roster` facade directly — the first call
triggers a scan and the result is cached for subsequent calls:

```php
use Laravel\Roster\Facades\Roster;

Roster::php()->uses('pest');
Roster::stack()->is(Stack::INERTIA_REACT);
```

Outside a Laravel container, or when you want an explicit handle, call
`Roster::scan()`:

```php
use Laravel\Roster\Roster;

$roster = Roster::scan();                              // project + system (default)
$roster = Roster::scan($basePath);
$roster = Roster::scan(detectSystem: false);           // project only
```

The examples below use `$roster` for clarity, but every call works on the
facade too.

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
$roster->php()->uses('pest');                          // alias
$roster->php()->uses('pestphp/pest');                  // raw
$roster->php()->uses('framework', '12.0.0');           // present and >= 12.0.0
$roster->php()->uses('framework', '12.0.0', '<');      // present and < 12.0.0
$roster->php()->package('pest')?->version();
$roster->php()->packages()->dev();

$roster->js()->uses('vue');
$roster->js()->uses('@inertiajs/react');
$roster->js()->uses('vue', '3.0.0');
$roster->js()->package('vue')?->major();
```

### Aliases

Every package can always be queried by its raw name (`laravel/pint`,
`@inertiajs/react`). On top of that, packages from the Laravel-ecosystem
namespaces get a short alias automatically so you can write
`$roster->php()->uses('pint')` instead of the full vendor-qualified string.

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

To register your own alias for a package, use `Roster::extend()`. Explicit
aliases always win over the auto-alias rules:

```php
use Laravel\Roster\Facades\Roster;
use Laravel\Roster\Registry;

Roster::extend(function (Registry $r) {
    $r->php('spatie/laravel-permission', alias: 'permission');
    $r->js('@tanstack/react-query', alias: 'react-query');
});

// Then either form works:
$roster->php()->uses('permission');
$roster->php()->uses('spatie/laravel-permission');
```

### Stack, test framework, frontend

```php
use Laravel\Roster\Enums\BrowserTestFramework;
use Laravel\Roster\Enums\Frontend;
use Laravel\Roster\Enums\Stack;
use Laravel\Roster\Enums\TestFramework;

$roster->stack()->is(Stack::INERTIA_REACT);
$roster->stack()->uses([Stack::INERTIA_REACT, Stack::INERTIA_VUE]);
$roster->stack()->all();                               // Stack[]

$roster->testFramework()?->is(TestFramework::PEST);
$roster->browserTestFrameworks()->uses(BrowserTestFramework::PLAYWRIGHT);

$roster->frontend()->is(Frontend::REACT);
```

Use `is()` to check for a single value and `uses()` to check for one or
more (it accepts either a single case or an array of cases).

### Starter kit

```php
use Laravel\Roster\Enums\StarterKit;

$roster->starterKit()->is(StarterKit::REACT);
$roster->starterKit()->all();                          // [] when none match
```

### Agents — AI agents + editors in one enum

```php
use Laravel\Roster\Enums\Agent;

$roster->agents()->configured()->all();                // filesystem signals
$roster->agents()->installed()->all();                 // binaries on PATH
$roster->agents()->isConfigured(Agent::CLAUDE_CODE);
$roster->agents()->isInstalled(Agent::CURSOR);

Agent::PHPSTORM->isEditor();
Agent::CLAUDE_CODE->isAi();
```

### JS package managers — project + system

```php
use Laravel\Roster\Enums\JsPackageManager;

$roster->js()->packageManagers()->configured()->is(JsPackageManager::PNPM);
$roster->js()->packageManagers()->installed()->all();
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
