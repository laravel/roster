# Upgrade Guide

## Upgrading to 1.0 from 0.x

Roster 1.0 is a breaking redesign. The signature of `Roster::scan()` is
unchanged, but everything else moved.

### Highlights

- `Packages` enum dropped. Packages are queried by alias or raw name.
- Top-level `$roster->uses(...)`, `usesVersion(...)`, `packages()` removed.
  Replaced by ecosystem surfaces: `$roster->php()` and `$roster->js()`.
- `uses()` and `usesVersion()` are now one method: `uses($name, $version = null, $operator = '>=')`.
  Default operator changes from `=` to `>=`.
- The `Roster` facade now lazily scans on first access and caches the result,
  so `Roster::php()->uses(...)` works without an explicit `Roster::scan()` first.
- New detectors: `stack()`, `testFramework()`, `browserTestFrameworks()`,
  `frontend()`, `starterKit()`, `agents()`, `approach()`.
- `Approaches` enum renamed `Approach` (singular). The `Approach` value
  class is removed in favour of the enum-only approach detection.
- `Ides` enum removed. `PHPSTORM` and `VSCODE` fold into the unified `Agent`
  enum alongside AI agents.
- `NodePackageManager` renamed `JsPackageManager`. `$roster->nodePackageManager()`
  is replaced by `$roster->js()->packageManagers()->configured()->all()`.
- Scan can skip machine probes: `Roster::scan(detectSystem: false)`.

### Migrating queries

| 0.x | 1.0 |
| --- | --- |
| `$roster->uses(Packages::INERTIA_LARAVEL)` | `$roster->php()->uses('inertia-laravel')` |
| `$roster->usesVersion(Packages::PEST, '3.0.0', '>=')` | `$roster->php()->uses('pest', '3.0.0')` |
| `$roster->usesVersion(Packages::PEST, '3.0.0', '=')` | `$roster->php()->uses('pest', '3.0.0', '=')` |
| `$roster->packages()` | `$roster->php()->packages()` and `$roster->js()->packages()` |
| `$roster->nodePackageManager()` | `$roster->js()->packageManagers()->configured()->all()` |
| `$package->majorVersion()` | `$package->major()` (returns `int`) |
| `$package->direct()` / `indirect()` | `$package->isDirect()` |
| `$roster->agents()->hasConfigured(...)` | `$roster->agents()->isConfigured(...)` |
| `$roster->agents()->hasInstalled(...)` | `$roster->agents()->isInstalled(...)` |

### New detectors

```php
use Laravel\Roster\Enums\Agent;
use Laravel\Roster\Enums\BrowserTestFramework;
use Laravel\Roster\Enums\Frontend;
use Laravel\Roster\Enums\Stack;
use Laravel\Roster\Enums\StarterKit;

$roster->stack()->is(Stack::INERTIA_REACT);
$roster->testFramework()?->is(TestFramework::PEST);
$roster->browserTestFrameworks()->uses(BrowserTestFramework::PLAYWRIGHT);
$roster->frontend()->is(Frontend::REACT);
$roster->starterKit()->is(StarterKit::REACT_WORKOS);
$roster->agents()->isConfigured(Agent::CLAUDE_CODE);
$roster->agents()->isInstalled(Agent::CURSOR);
```

`EnumSet` exposes both `is()` and `uses()` — they are aliases. Use whichever
reads more naturally for the surface in question.

### Registering custom aliases

```php
use Laravel\Roster\Facades\Roster;
use Laravel\Roster\Registry;

Roster::extend(function (Registry $r) {
    $r->php('spatie/laravel-permission', alias: 'permission');
    $r->js('@tanstack/react-query', alias: 'react-query');
});
```

### Agent detection scope

Roster only reports static signals: filesystem markers (`configured()`) and
binaries on PATH (`installed()`). Runtime detection ("which agent is executing
this process right now?") stays in `laravel/agent-detector` and must be called
directly when needed.
