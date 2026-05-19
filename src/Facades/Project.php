<?php

declare(strict_types=1);

namespace Laravel\Roster\Facades;

use Illuminate\Support\Facades\Facade;
use Laravel\Roster\ProjectManager;

/**
 * @method static \Laravel\Roster\ProjectManager extend(callable $callback)
 * @method static \Laravel\Roster\Registry registry()
 * @method static \Laravel\Roster\ProjectManager ttl(int $seconds)
 * @method static \Laravel\Roster\ProjectManager withoutCache()
 * @method static \Laravel\Roster\Project scan(?string $basePath = null)
 * @method static \Laravel\Roster\ProjectManager fresh()
 * @method static \Laravel\Roster\Project instance()
 * @method static \Laravel\Roster\Ecosystems\PhpEcosystem php()
 * @method static \Laravel\Roster\Ecosystems\JsEcosystem js()
 * @method static \Laravel\Roster\Support\EnumSet<\Laravel\Roster\Enums\Stack> stack()
 * @method static \Laravel\Roster\Enums\TestFramework|null testFramework()
 * @method static \Laravel\Roster\Support\EnumSet<\Laravel\Roster\Enums\BrowserTestFramework> browserTestFrameworks()
 * @method static \Laravel\Roster\Support\EnumSet<\Laravel\Roster\Enums\Frontend> frontend()
 * @method static \Laravel\Roster\Support\EnumSet<\Laravel\Roster\Enums\StarterKit> starterKit()
 * @method static \Laravel\Roster\Support\EnumSet<\Laravel\Roster\Enums\Agent> agents()
 * @method static \Laravel\Roster\Support\EnumSet<\Laravel\Roster\Enums\Approach> approach()
 * @method static string json()
 *
 * @see ProjectManager
 */
class Project extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ProjectManager::class;
    }
}
