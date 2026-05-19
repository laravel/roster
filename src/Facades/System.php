<?php

declare(strict_types=1);

namespace Laravel\Roster\Facades;

use Illuminate\Support\Facades\Facade;
use Laravel\Roster\SystemManager;

/**
 * @method static \Laravel\Roster\SystemManager ttl(int $seconds)
 * @method static \Laravel\Roster\SystemManager withoutCache()
 * @method static \Laravel\Roster\System scan()
 * @method static \Laravel\Roster\SystemManager fresh()
 * @method static \Laravel\Roster\System instance()
 * @method static \Laravel\Roster\Support\InstalledSet<\Laravel\Roster\Enums\Agent> agents()
 * @method static \Laravel\Roster\Support\InstalledSet<\Laravel\Roster\Enums\JsPackageManager> packageManagers()
 * @method static string json()
 *
 * @see SystemManager
 */
class System extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SystemManager::class;
    }
}
