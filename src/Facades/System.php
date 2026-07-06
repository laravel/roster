<?php

declare(strict_types=1);

namespace Laravel\Roster\Facades;

use Illuminate\Support\Facades\Facade;
use Laravel\Roster\SystemManager;

/**
 * @method static \Laravel\Roster\System scan()
 * @method static \Laravel\Roster\System instance()
 * @method static \Laravel\Roster\Support\EnumSet<\Laravel\Roster\Enums\Agent> agents()
 * @method static \Laravel\Roster\Support\EnumSet<\Laravel\Roster\Enums\Editor> editors()
 * @method static \Laravel\Roster\Support\EnumSet<\Laravel\Roster\Enums\JsPackageManager> jsPackageManagers()
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
