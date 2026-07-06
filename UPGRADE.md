# Upgrade Guide

- [Upgrading To 1.0 From 0.x](#upgrading-to-10-from-0x)

## High Impact Changes

- [Updating Dependencies](#updating-dependencies)
- [The `Roster` Facade](#the-roster-facade)
- [The `Packages` Enum](#the-packages-enum)
- [Version Constraints](#version-constraints)

## Medium Impact Changes

- [The `Package` Class](#the-package-class)
- [Stack Detection](#stack-detection)
- [The `Ides` Enum](#the-ides-enum)
- [JS Package Managers](#js-package-managers)
- [Removed Detections](#removed-detections)

## Low Impact Changes

- [The `Approaches` Enum](#the-approaches-enum)
- [The `roster:scan` Command](#the-rosterscan-command)

## Upgrading To 1.0 From 0.x

#### Estimated Upgrade Time: 10 Minutes

> [!NOTE]
> We attempt to document every possible breaking change. Since some of these breaking changes are in obscure parts of the package, only a portion of these changes may actually affect your application.

### Updating Dependencies

**Likelihood Of Impact: High**

You should update the following dependency in your application's `composer.json` file:

- `laravel/roster` to `^1.0`

### The `Roster` Facade

**Likelihood Of Impact: High**

The `Laravel\Roster\Facades\Roster` facade has been removed and replaced by the `Laravel\Roster\Facades\Project` facade, which reads your project's lockfiles and configuration markers. Calls to `Roster::scan()` should be replaced with `Project::scan()`:

```php
// 0.x...
use Laravel\Roster\Facades\Roster;

Roster::scan();

// 1.0...
use Laravel\Roster\Facades\Project;

Project::scan();
```

### The `Packages` Enum

**Likelihood Of Impact: High**

The curated `Packages` enum and its alias registry have been removed. Package checks now accept the raw package names you would write in your `composer.json` or `package.json` files, and are invoked on the `php()` or `js()` ecosystem of the `Project` facade:

```php
// 0.x...
$roster->uses(Packages::INERTIA_LARAVEL);

// 1.0...
Project::php()->uses('inertiajs/inertia-laravel');
```

In addition, the `usesVersion` method has been removed. Its operator argument is replaced by a composer-semver constraint string passed as the second argument to the `uses` method:

```php
// 0.x...
$roster->usesVersion(Packages::PEST, '3.0.0', '>=');
$roster->usesVersion(Packages::PEST, '3.0.0', '=');

// 1.0...
Project::php()->uses('pestphp/pest', '>=3.0.0');
Project::php()->uses('pestphp/pest', '3.0.0');
```

### Version Constraints

**Likelihood Of Impact: High**

A bare version string such as `1.2.3` now means an exact match. In 0.x, a bare version defaulted to a `>=` comparison. To preserve the previous behavior, you should write the operator explicitly:

```php
// 0.x behavior of uses('...', '1.2.3')...
Project::php()->uses('pestphp/pest', '>=1.2.3');
```

The constraint argument accepts any composer-semver expression, such as `^1.2.3`, `~1.2`, `>=11 <14`, or `1.0 || ^2.0`.

### The `Package` Class

**Likelihood Of Impact: Medium**

Several methods on the `Laravel\Roster\Package` class have been renamed:

| 0.x | 1.0 |
| --- | --- |
| `$package->majorVersion()` | `$package->major()` (returns `?int`, `null` when the version is unknown) |
| `$package->direct()` / `$package->indirect()` | `$package->isDirect()` |

Package collections are now retrieved per ecosystem via `Project::php()->packages()` and `Project::js()->packages()` instead of a single `$roster->packages()` call.

### Stack Detection

**Likelihood Of Impact: Medium**

The `stack` method has been renamed to `stacks`, now lives on the `Project` facade, and returns an `EnumSet` containing every detected stack. Membership is checked via the `uses` method:

```php
// 0.x...
$roster->stack();

// 1.0...
Project::stacks()->uses(Stack::INERTIA_REACT);
```

### The `Ides` Enum

**Likelihood Of Impact: Medium**

The `Ides` enum has been removed and split into two enums: `Laravel\Roster\Enums\Agent` for AI coding tools (Claude Code, Cursor, Codex, etc.) and `Laravel\Roster\Enums\Editor` for IDEs (PHPStorm, VSCode, Zed, Sublime Text). Each is detected through the project's filesystem markers:

```php
Project::agents()->uses(Agent::CLAUDE_CODE);
Project::editors()->uses(Editor::PHPSTORM);
```

### JS Package Managers

**Likelihood Of Impact: Medium**

The `NodePackageManager` enum has been renamed to `JsPackageManager`, and the `nodePackageManager` method has been replaced by `Project::js()->packageManager()`, which returns a nullable `JsPackageManager` based on the committed lockfile:

```php
// 0.x...
$roster->nodePackageManager();

// 1.0...
Project::js()->packageManager(); // ?JsPackageManager
```

Bun is detected via either `bun.lock` or `bun.lockb`. Since `bun.lockb` is a binary format, package scanning falls back to the direct dependencies declared in `package.json` when only `bun.lockb` is committed.

### Removed Detections

**Likelihood Of Impact: Medium**

The `TestFramework` and `StarterKit` detections have been removed. Test frameworks may be checked as ordinary packages instead:

```php
Project::php()->uses('pestphp/pest');
```

Detection of binaries installed on the host machine has also been removed — Roster now only reports on the project itself.

### The `Approaches` Enum

**Likelihood Of Impact: Low**

The `Approaches` enum has been renamed to `Approach` (singular), and its wrapping value class has been removed. Detected approaches are reported through the `approaches` method on the `Project` facade, which covers both directory conventions and source-code conventions:

```php
Project::approaches()->uses(Approach::ACTION);
Project::approaches()->uses([Approach::ACTION, Approach::DDD]);
```

### The `roster:scan` Command

**Likelihood Of Impact: Low**

The `roster:scan` Artisan command now requires a directory argument and emits the project surface as a JSON document. You may pass `--approaches` to include source-code approach detection:

```bash
php artisan roster:scan /path/to/project --approaches
```
