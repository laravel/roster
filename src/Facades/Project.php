<?php

declare(strict_types=1);

namespace Laravel\Roster\Facades;

use Illuminate\Support\Facades\Facade;
use Laravel\Roster\ProjectManager;

/**
 * @method static \Laravel\Roster\ProjectScan scan(?string $basePath = null)
 * @method static \Laravel\Roster\ProjectScan fresh(?string $basePath = null)
 * @method static \Laravel\Roster\ProjectScan instance()
 * @method static \Laravel\Roster\Ecosystems\Ecosystem php()
 * @method static \Laravel\Roster\Ecosystems\JsEcosystem js()
 * @method static \Laravel\Roster\Support\EnumSet<\Laravel\Roster\Enums\Stack> stacks()
 * @method static \Laravel\Roster\Support\EnumSet<\Laravel\Roster\Enums\BrowserTestFramework> browserTestFrameworks()
 * @method static \Laravel\Roster\Support\EnumSet<\Laravel\Roster\Enums\Frontend> frontends()
 * @method static \Laravel\Roster\Support\EnumSet<\Laravel\Roster\Enums\Agent> agents()
 * @method static \Laravel\Roster\Support\EnumSet<\Laravel\Roster\Enums\Editor> editors()
 * @method static \Laravel\Roster\Support\ApproachSet approaches()
 * @method static array<string, mixed> toArray()
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
