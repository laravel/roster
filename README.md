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
- [Caching](#caching)
- [The `roster:scan` Command](#the-rosterscan-command)
- [Upgrading](#upgrading)
- [Contributing](#contributing)
- [Code of Conduct](#code-of-conduct)
- [Security Vulnerabilities](#security-vulnerabilities)
- [License](#license)

## Introduction

Laravel Roster is a detection package for the Laravel ecosystem. It reads your project's lockfiles and configuration markers and can optionally inspect source code to determine what the project uses.

The `Project` facade reports package dependencies, the application's stack and frontend, browser test frameworks, configured AI agents and editors, the JS package manager indicated by the committed lockfile, and conventions adopted by the codebase.

## Installation

You may install Roster as a development dependency via the Composer package manager:

```bash
composer require laravel/roster --dev
```

## Basic Usage

Within a Laravel application, you may call the `Project` facade directly. The first call triggers a scan, and the result is reused by subsequent facade calls:

```php
use Laravel\Roster\Enums\Stack;
use Laravel\Roster\Facades\Project;

Project::php()->uses('pestphp/pest');
Project::stacks()->uses(Stack::InertiaReact);
```

Outside a Laravel service container, or when you want an explicit project instance, instantiate the manager directly. It runs without caching when no container or cache driver is available:

```php
use Laravel\Roster\ProjectManager;

$projects = new ProjectManager;

$project = $projects->scan(); // Uses base_path() or getcwd().
$project = $projects->scan($basePath);
```

The following examples use `$project` for clarity, but the same calls are available through the facade.

## Detecting Packages

Packages are exposed through two ecosystems: `php()` for Composer packages and `js()` for JavaScript packages managed by npm, pnpm, Yarn, or Bun. Both ecosystems provide the same methods:

```php
$ecosystem->uses(string|array $packages, ?string $constraint = null): bool
$ecosystem->usesAll(array $packages): bool
```

The `uses` method returns `true` when **any** of the given packages is present, while the `usesAll` method returns `true` only when **every** package is present. Use the package names that appear in `composer.json` or `package.json`:

```php
$project->php()->uses('pestphp/pest');
$project->js()->uses('@inertiajs/react');
```

### Version Constraints

You may pass a version constraint as the second argument to the `uses` method. It accepts any Composer Semver constraint, such as `^1.2.3`, `~1.2`, `>=11 <14`, or `1.0 || ^2.0`. A bare version such as `1.2.3` requires an exact match. When the constraint is omitted, only the package's presence is checked:

```php
$project->php()->uses('laravel/framework', '^12.0');
$project->php()->uses('laravel/framework', '>=11 <14');
```

### Checking Multiple Packages

To check whether **any** of several packages are present, you may pass an indexed array of names to the `uses` method. Pass an associative array to specify constraints for individual packages:

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

You may also retrieve the underlying `Package` instance or collection. The `usesDirect` method checks whether a package is a *direct* dependency (declared in your manifest rather than pulled in transitively). When passed an array, it returns `true` if any listed package is direct. The collection provides `dev`, `production`, and `direct` filters:

```php
$project->php()->package('pestphp/pest')?->version();
$project->js()->package('vue')?->major();
$project->php()->usesDirect('livewire/livewire');
$project->php()->usesDirect(['livewire/livewire', 'livewire/volt']);
$project->php()->packages()->dev();
$project->php()->packages()->direct();
```

> [!NOTE]
> The development classification of *transitive* packages is available only for Composer and npm lockfiles. Yarn, pnpm, and Bun lockfiles report transitive packages as production dependencies. Direct dependencies are classified using authoritative lockfile metadata when available and manifest data otherwise.

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

The `uses` method accepts either a single case or an array of cases and returns `true` when **any** case is present, while the `usesAll` method returns `true` only when **every** case is present.

## Detecting Agents and Editors

Agents (AI coding tools such as Claude Code, Cursor, and Codex) and editors (IDEs such as PhpStorm and VS Code) are exposed through separate enums. Roster detects them through filesystem markers such as `.claude`, `.cursor`, `.idea`, and `AGENTS.md`:

```php
use Laravel\Roster\Enums\Agent;
use Laravel\Roster\Enums\Editor;

$project->agents()->uses(Agent::ClaudeCode);
$project->agents()->uses([Agent::ClaudeCode, Agent::Cursor]);
$project->editors()->uses(Editor::PhpStorm);
```

## Detecting JS Package Managers

The `$project->js()->packageManager()` method reports the package manager indicated by the project's lockfile as a nullable enum (`package-lock.json` indicates npm, `pnpm-lock.yaml` indicates pnpm, and so on):

```php
use Laravel\Roster\Enums\JsPackageManager;

$project->js()->packageManager() === JsPackageManager::Pnpm;
```

You may also check for a specific package manager via the `usesPackageManager` method, which accepts an enum case or its string value:

```php
$project->js()->usesPackageManager(JsPackageManager::Pnpm);
$project->js()->usesPackageManager('pnpm');
```

Projects should commit only one supported JavaScript lockfile. If multiple lockfiles are present, Roster selects the first match in this order: npm, pnpm, Yarn, then Bun.

## Detecting Approaches

The `approaches` method inspects the project's **own source code**, not its manifests, and reports which stylistic conventions the application has adopted:

- `fillable` vs `guarded` mass assignment (including the `protected $fillable` property and `#[Fillable]` attribute)
- enum case capitalization (`SCREAMING_SNAKE_CASE`, `PascalCase`, or `camelCase`)
- pipe vs array validation-rule syntax
- inline validation vs form requests (`$request->validate([...])` vs dedicated `rules()` classes under `Http/Requests`)
- command configuration via the `#[AsCommand]` attribute vs the `$signature` or `$description` property
- notifications sent via `$notifiable->notify()` vs the `Notification` facade
- authorization via gates, `$user->can()`, or `$this->authorize()`
- authenticated user retrieval via the `Auth` facade, `$request->user()`, or the `auth()` helper
- model key style: UUID (`HasUuids`), ULID (`HasUlids`), or the default auto-incrementing key

You may check for one or more approaches or retrieve all detected results:

```php
use Laravel\Roster\Enums\Approach;

$project->approaches()->uses(Approach::MassAssignmentFillable);
$project->approaches()->uses([
    Approach::ValidationPipeSyntax,
    Approach::ValidationArraySyntax,
]);
$project->approaches()->all(); // Collection<string, ApproachResult>
```

Detection is best-effort: Roster uses lightweight pattern matching rather than a full parser, so an unusual file may abstain or be classified based on a comment or string literal. Approaches are therefore reported with a confidence ratio rather than as exact answers.

A stylistic approach is reported only when it receives at least three votes and at least 80% of the votes cast. Each file casts at most one vote, except that enum capitalization receives one vote per enum case. Consequently, a 2/3 majority is rejected, a 4/5 majority passes, and an evenly split codebase produces no result. A file that mixes styles votes for its majority style and abstains when tied.

Each `ApproachResult` exposes the winning `approach`, its raw `confidence` ratio, the `matched` and `total` vote counts, and the `paths` of the files that voted. You may retrieve a result via the `result` method:

```php
$result = $project->approaches()->result(Approach::MassAssignmentFillable);

$result->confidence; // 0.9
$result->matched;    // 9
$result->total;      // 10
$result->paths;      // ['/app/Models/User.php', ...]
```

Roster discovers source files by combining the PSR-4 autoload roots in `composer.json` with `app/`. It matches subdirectories such as `Models/` anywhere beneath those roots, so it also scans modular layouts such as `src/Domain/Orders/Models/`. The `vendor/` and `node_modules/` directories, as well as hidden directories, are always excluded.

Because source files can change without affecting a lockfile, approaches are never persisted with a cached scan. They are computed lazily once per scan instance and only when requested. The `toArray()` and `json()` methods omit them, while the `roster:scan` command accepts an `--approaches` flag to include them in its output.

## Caching

The first call through the `Project` facade scans the default project and memoizes the result for the remainder of the process. Across processes, Roster uses your application's configured cache driver. The cache key includes a hash of supported manifests and lockfiles, along with the presence of detector marker paths, so changes such as an edit to `composer.lock` or the addition of a `.claude` directory invalidate the persisted cache. Roster falls back to a direct scan when no cache driver is configured or the driver fails.

In long-running processes such as Octane or queue workers, the memoized instance is kept until the worker restarts. You may call `Project::fresh()` to bypass both the memoized result and the persisted cache and force a new scan at any time.

## The `roster:scan` Command

The `roster:scan` Artisan command scans a directory and emits the project surface as a JSON document. When the directory is omitted, the application's base path is scanned:

```bash
php artisan roster:scan
php artisan roster:scan /path/to/project
```

You may pass `--approaches` to include approach detection for PHP files under the project's PSR-4 autoload roots and `app/` directory:

```bash
php artisan roster:scan /path/to/project --approaches
```

## Upgrading

Please consult the [upgrade guide](UPGRADE.md) when upgrading from 0.x.

## Contributing

Thank you for considering contributing to Roster! You can find the contribution guide in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

To help ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

Please review [our security policy](https://github.com/laravel/roster/security/policy) for instructions on reporting security vulnerabilities.

## License

Laravel Roster is open-source software licensed under the [MIT license](LICENSE.md).
