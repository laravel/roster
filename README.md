# Laravel Roster

<p align="center">
<a href="https://github.com/laravel/roster/actions"><img src="https://github.com/laravel/roster/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/roster"><img src="https://img.shields.io/packagist/dt/laravel/roster" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/roster"><img src="https://img.shields.io/packagist/v/laravel/roster" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/roster"><img src="https://img.shields.io/packagist/l/laravel/roster" alt="License"></a>
</p>

- [Introduction](#introduction)
- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Detecting Packages](#detecting-packages)
    - [Version Constraints](#version-constraints)
    - [Checking Multiple Packages](#checking-multiple-packages)
    - [Retrieving Packages](#retrieving-packages)
- [Detecting Stacks and Frontends](#detecting-stacks-and-frontends)
- [Detecting Agents and Editors](#detecting-agents-and-editors)
- [Detecting JS Package Managers](#detecting-js-package-managers)
- [Detecting Approaches](#detecting-approaches)
    - [Source Conventions](#source-conventions)
    - [Custom Conventions](#custom-conventions)
- [Caching](#caching)
- [The `roster:scan` Command](#the-rosterscan-command)
- [Upgrading](#upgrading)
- [Contributing](#contributing)
- [Code of Conduct](#code-of-conduct)
- [Security Vulnerabilities](#security-vulnerabilities)
- [License](#license)

## Introduction

Laravel Roster is a detection package for the Laravel ecosystem. It reads your project's lockfiles, configuration markers, and (optionally) probes the host machine to answer questions about what is in use.

The `Project` facade reads your project's lockfiles and configuration markers to report installed packages, the application's stack, the frontend in use, browser test frameworks, configured AI agents and editors, the committed JS package manager, and the conventions the codebase has adopted.

## Installation

You may install Roster as a development dependency via the Composer package manager:

```bash
composer require laravel/roster --dev
```

## Basic Usage

Within a Laravel application, you may call the `Project` facade directly. The first call triggers a scan and the result is cached for subsequent calls:

```php
use Laravel\Roster\Enums\Stack;
use Laravel\Roster\Facades\Project;

Project::php()->uses('pestphp/pest');
Project::stacks()->uses(Stack::InertiaReact);
```

Outside of a Laravel container, or when you would like an explicit handle, you may use the static `scan` method:

```php
use Laravel\Roster\Project;

$project = Project::scan();          // uses base_path() / getcwd()
$project = Project::scan($basePath);
```

Note that `Laravel\Roster\Project::scan()` performs a fresh scan on every call, while the `Laravel\Roster\Facades\Project` facade caches — take care to import the one you intend.

The examples that follow use `$project` for clarity, but every call works on the facade.

## Detecting Packages

Packages are exposed through two ecosystems: `php()` for Composer and `js()` for npm, pnpm, yarn, and bun. Both ecosystems share the same surface:

```php
$ecosystem->uses(string|array $packages, ?string $constraint = null): bool
$ecosystem->usesAll(array $packages): bool
```

The `uses` method returns `true` when **any** of the given packages is present, while the `usesAll` method returns `true` only when **every** package is present. Names are the raw package names you would write in your `composer.json` or `package.json` files:

```php
$project->php()->uses('pestphp/pest');
$project->js()->uses('@inertiajs/react');
```

### Version Constraints

You may pass a version constraint as the second argument to the `uses` method. The constraint accepts any composer-semver string, such as `^1.2.3`, `~1.2`, `>=11 <14`, or `1.0 || ^2.0`. A bare version like `1.2.3` means an exact match. When omitted, only the package's presence is checked:

```php
$project->php()->uses('laravel/framework', '^12.0');
$project->php()->uses('laravel/framework', '>=11 <14');
```

### Checking Multiple Packages

To check if **any** of several packages are present, you may pass an indexed array of names to the `uses` method. Pass an associative array when you would like per-package constraints:

```php
$project->php()->uses(['pestphp/pest', 'phpunit/phpunit']);

$project->php()->uses([
    'pestphp/pest' => '^3.0',
    'phpunit/phpunit' => '^10.0',
]);
```

To require that **all** of several packages are present, you may use the `usesAll` method:

```php
$project->php()->usesAll(['pestphp/pest', 'laravel/framework']);

$project->php()->usesAll([
    'pestphp/pest' => '^3.0',
    'laravel/framework' => '^11.0',
]);
```

The JS ecosystem behaves the same way:

```php
$project->js()->uses(['vue' => '^3.0', 'react' => '^18.0']);
$project->js()->usesAll(['vue', '@inertiajs/vue3']);
```

> [!WARNING]
> The array passed to the `uses` and `usesAll` methods must be either entirely indexed (just names) or entirely associative (name to constraint). Mixing the two shapes throws an `InvalidArgumentException`.

### Retrieving Packages

You may also retrieve the underlying `Package` instance or collection. The `usesDirect` method checks that a package is a *direct* dependency (declared in your manifest rather than pulled in transitively), and the collection exposes `dev`, `production`, and `direct` filters:

```php
$project->php()->package('pestphp/pest')?->version();
$project->js()->package('vue')?->major();
$project->php()->usesDirect('livewire/livewire');
$project->php()->usesDirect(['livewire/livewire', 'livewire/volt']);
$project->php()->packages()->dev();
$project->php()->packages()->direct();
```

> [!NOTE]
> The dev classification of *transitive* packages is only available for Composer and npm lockfiles. Yarn, pnpm, and bun lockfiles report transitive packages as production dependencies; direct dependencies are always classified from your manifest.

## Detecting Stacks and Frontends

The `stacks`, `frontends`, and `browserTestFrameworks` methods on the `Project` surface return an `EnumSet` containing every detected case. You may invoke the `uses` method to check for membership, the `usesAll` method to require every given case, and the `all` method to retrieve every detected case:

```php
use Laravel\Roster\Enums\BrowserTestFramework;
use Laravel\Roster\Enums\Frontend;
use Laravel\Roster\Enums\Stack;

$project->stacks()->uses(Stack::InertiaReact);
$project->stacks()->all();                             // Stack[]

$project->browserTestFrameworks()->uses(BrowserTestFramework::Playwright);
$project->browserTestFrameworks()->uses([
    BrowserTestFramework::Playwright,
    BrowserTestFramework::Cypress,
]);
$project->browserTestFrameworks()->usesAll([
    BrowserTestFramework::Playwright,
    BrowserTestFramework::Cypress,
]);

$project->frontends()->uses(Frontend::React);
```

The `uses` method accepts either a single case or an array of cases and returns `true` when **any** is present, while the `usesAll` method returns `true` only when **every** case is present.

## Detecting Agents and Editors

Agents (AI coding tools such as Claude Code, Cursor, and Codex) and editors (IDEs such as PHPStorm and VSCode) are exposed through separate enums, detected through filesystem markers like `.claude`, `.cursor`, `.idea`, or `AGENTS.md`:

```php
use Laravel\Roster\Enums\Agent;
use Laravel\Roster\Enums\Editor;

$project->agents()->uses(Agent::ClaudeCode);
$project->agents()->uses([Agent::ClaudeCode, Agent::Cursor]);
$project->editors()->uses(Editor::PhpStorm);
```

## Detecting JS Package Managers

The `$project->js()->packageManager()` method reports the package manager *committed* to the project as a single nullable enum, based on which lockfile is present (`package-lock.json`, `pnpm-lock.yaml`, and so on):

```php
use Laravel\Roster\Enums\JsPackageManager;

$project->js()->packageManager() === JsPackageManager::Pnpm;
```

## Detecting Approaches

The `approaches` method reports the stylistic conventions a project has adopted, read from the source code itself.

### Source Conventions

The `approaches` method inspects the project's **own source code** — not its manifests — and reports which stylistic conventions the application has adopted: `fillable` vs `guarded` mass assignment (detected in both the `protected $fillable` property and `#[Fillable]` attribute spellings), enum case casing, pipe vs array validation rule syntax, and inline validation vs form requests (`$request->validate([...])` versus dedicated `rules()` classes under `Http/Requests`):

```php
use Laravel\Roster\Enums\Approach;

$project->approaches()->uses(Approach::MassAssignmentFillable); // is this the dominant style?
$project->approaches()->uses([                                    // any-of, like EnumSet
    Approach::ValidationPipeSyntax,
    Approach::ValidationArraySyntax,
]);
$project->approaches()->all();                                    // Collection<string, ApproachResult>
```

Detection is best-effort: source is read with lightweight pattern matching rather than a full parser, so an unusual file may abstain or be classified from a comment or string literal. This is why approaches are reported as a confidence-weighted vote rather than an exact answer.

A stylistic approach is only reported when it is backed by enough evidence: at least 5 votes (one per voting file — or one per enum case for casing), with more than 80% of them for the winning style — so a 4/5 majority is rejected, 90/100 passes, and an evenly split codebase stays silent. A file that mixes styles votes for its majority style and abstains on a tie.

Each `ApproachResult` exposes the winning `approach`, its raw `confidence` ratio, the `matched` and `total` vote counts, and the `paths` of the files that voted. You may retrieve a result via the `result` method:

```php
$result = $project->approaches()->result(Approach::MassAssignmentFillable);

$result->confidence; // 0.9
$result->matched;    // 9
$result->total;      // 10
$result->paths;      // ['/app/Models/User.php', ...]
```

Source files are discovered from the `composer.json` PSR-4 autoload roots unioned with `app/`, and subdirectories such as `Models/` are matched anywhere beneath a root, so modular layouts like `src/Domain/Orders/Models/` are sampled too. `vendor/`, `node_modules/`, and hidden directories are always excluded.

Because source files change without touching any lockfile, approaches are never persisted with the cached scan — they are computed lazily per process, and only when you ask for them: `toArray()` and `json()` stay cheap and omit them, while the `roster:scan` command accepts an `--approaches` flag to include them in its output.

### Custom Conventions

You may teach Roster your own source conventions using the `extendApproaches` method, typically within the `boot` method of a service provider. Define the competing styles as your own backed enum, then register a callback that receives each source file's contents and path and returns the style the file votes for — or `null` to abstain:

```php
use Laravel\Roster\Facades\Project;

enum Persistence: string
{
    case Repository = 'acme.repository';
    case DirectEloquent = 'acme.direct-eloquent';
}

// In a service provider's boot method...
Project::extendApproaches(
    fn (string $contents, string $path): ?Persistence => match (true) {
        str_contains($contents, 'RepositoryInterface') => Persistence::Repository,
        str_contains($contents, '::query()') => Persistence::DirectEloquent,
        default => null,
    },
    in: 'Models',
);
```

The `in` argument restricts voting to files beneath the given subdirectory of any source root, just like the built-in conventions; omit it to sample every source file. Custom conventions then flow through the same election as the built-ins — the vote and confidence thresholds apply, and results are queried the same way:

```php
Project::approaches()->uses(Persistence::Repository);
Project::approaches()->result(Persistence::Repository)?->confidence;
```

Registrations take effect immediately: if approaches were already computed, the next call to the `approaches` method re-detects with the new convention included.

## Caching

The first call to the `Project` facade scans once and memoizes the result for the remainder of the process. Across processes, scans are cached using your application's configured cache driver, keyed on a hash of your lockfile contents and detector marker directories — so edits to `composer.lock` or a newly added `.claude` directory invalidate the persisted cache automatically. Roster gracefully falls back to a direct scan when no cache driver is configured or the driver fails.

In long-running processes such as Octane or queue workers, the memoized instance is kept until the worker restarts. You may call `Project::fresh()` to bypass both the memo and the persisted cache and force a re-read at any time.

## The `roster:scan` Command

The `roster:scan` Artisan command scans a directory and emits the project surface as a JSON document. When the directory is omitted, the application's base path is scanned:

```bash
php artisan roster:scan
php artisan roster:scan /path/to/project
```

You may pass `--approaches` to include source-code approach detection (which scans every PHP source file):

```bash
php artisan roster:scan /path/to/project --approaches
```

## Upgrading

Please consult the [upgrade guide](UPGRADE.md) when upgrading from 0.x.

## Contributing

Thank you for considering contributing to Roster! The contribution guide may be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

Please review [our security policy](https://github.com/laravel/roster/security/policy) on how to report security vulnerabilities.

## License

Laravel Roster is open-sourced software licensed under the [MIT license](LICENSE.md).
