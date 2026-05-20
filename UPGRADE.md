# Upgrade Guide

## Upgrading to 1.0 from 0.x

Roster 1.0 redesigns the public API. The `Roster` facade is replaced by two surfaces, the `Packages` enum is gone, and several detection methods have moved or changed shape. The tables below cover every migration.

### Facades

The single `Roster` facade is replaced by `Project` (lockfiles, configuration markers) and `System` (binaries on `PATH`):

| 0.x | 1.0 |
| --- | --- |
| `Laravel\Roster\Facades\Roster` | `Laravel\Roster\Facades\Project` + `Laravel\Roster\Facades\System` |
| `Roster::scan()` | `Project::scan()` and `System::scan()` |

### Packages

The curated `Packages` enum and the alias registry have both been removed. Pass raw package names from `composer.json` or `package.json` instead:

| 0.x | 1.0 |
| --- | --- |
| `$roster->uses(Packages::INERTIA_LARAVEL)` | `Project::php()->uses('inertiajs/inertia-laravel')` |
| `$roster->usesVersion(Packages::PEST, '3.0.0', '>=')` | `Project::php()->uses('pestphp/pest', '>=3.0.0')` |
| `$roster->usesVersion(Packages::PEST, '3.0.0', '=')` | `Project::php()->uses('pestphp/pest', '3.0.0')` |
| `$roster->packages()` | `Project::php()->packages()` and `Project::js()->packages()` |
| `$package->majorVersion()` | `$package->major()` (returns `int`) |
| `$package->direct()` / `$package->indirect()` | `$package->isDirect()` |

The `$constraint` argument now accepts any composer-semver string (`^1.2.3`, `~1.2`, `>=11 <14`, `1.0 || ^2.0`). A bare version such as `1.2.3` means an exact match instead of the old `>=1.2.3` default. To preserve the old behavior, write `'>=1.2.3'`.

### Batch checks

You may pass an array to check several packages at once. An indexed array means "any of these"; an associative array carries per-package constraints. A separate `usesAll` method requires every package to be present:

```php
Project::php()->uses(['pestphp/pest', 'phpunit/phpunit']);

Project::php()->uses([
    'pestphp/pest' => '^3.0',
    'phpunit/phpunit' => '^10.0',
]);

Project::php()->usesAll(['pestphp/pest', 'laravel/framework']);
```

Mixing indexed and associative keys throws `InvalidArgumentException`.

### Stack, frontend, browser test frameworks

| 0.x | 1.0 |
| --- | --- |
| `$roster->stack()` | `Project::stack()->uses(Stack::INERTIA_REACT)` |
| n/a | `Project::frontend()->uses(Frontend::REACT)` |
| n/a | `Project::browserTestFrameworks()->uses(BrowserTestFramework::PLAYWRIGHT)` |
| n/a | `Project::approach()->uses(Approach::ACTION)` |

`TestFramework` and `StarterKit` detection have been removed.

### Agents and editors

The `Ides` enum is gone. AI agents (Claude Code, Cursor, Codex, etc.) and editors (PHPStorm, VSCode, Zed, Sublime Text) are now two separate enums. Each is reported on both surfaces:

| Signal | 1.0 |
| --- | --- |
| Marker file in repo (`.claude`, `.cursor`, `AGENTS.md`) | `Project::agents()->uses(Agent::CLAUDE_CODE)` |
| Binary on `PATH` (`claude`, `cursor`) | `System::agents()->isInstalled(Agent::CURSOR)` |
| IDE marker (`.idea`, `.vscode`, `.zed`) | `Project::editors()->uses(Editor::PHPSTORM)` |
| IDE binary (`code`, `zed`, `subl`) | `System::editors()->isInstalled(Editor::VSCODE)` |

### JS package managers

| 0.x | 1.0 |
| --- | --- |
| `NodePackageManager` enum | `JsPackageManager` enum |
| `$roster->nodePackageManager()` | `Project::js()->packageManager()` returns `?JsPackageManager` |
| n/a | `System::js()->packageManagers()->isInstalled(JsPackageManager::BUN)` |

Bun is detected via either `bun.lock` or `bun.lockb`.

### Approaches

The `Approaches` enum is now `Approach` (singular). The wrapping value class is gone:

```php
Project::approach()->uses(Approach::ACTION);
Project::approach()->uses([Approach::ACTION, Approach::DDD]);
```

### Caching

Both managers ship with a lazy cache backed by the application's configured cache driver. `Project` keys on a hash of lockfile contents, so edits to `composer.lock` or `package-lock.json` invalidate automatically. `System` is TTL-only with a one-hour default and flushes the binary-lookup cache on `fresh()`. Both fall back to a direct scan when no driver is configured or the driver fails:

```php
Project::ttl(3600)->fresh();
System::withoutCache();
```

### `roster:scan` Artisan command

The `roster:scan` command emits a combined JSON document with the project surface at the top level and a `system` key when the host probe is included. Pass `--no-system` to skip the probe.
