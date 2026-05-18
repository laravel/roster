<?php

declare(strict_types=1);

namespace Laravel\Roster\Facades;

use Illuminate\Support\Facades\Facade;
use Laravel\Roster\RosterManager;

/**
 * @method static \Laravel\Roster\RosterManager extend(callable $callback)
 * @method static \Laravel\Roster\Registry registry()
 * @method static \Laravel\Roster\Roster scan(?string $basePath = null, bool $detectSystem = true)
 * @method static \Laravel\Roster\RosterManager fresh()
 * @method static \Laravel\Roster\Roster instance()
 * @method static \Laravel\Roster\Ecosystems\PhpEcosystem php()
 * @method static \Laravel\Roster\Ecosystems\JsEcosystem js()
 * @method static \Laravel\Roster\Support\EnumSet<\Laravel\Roster\Enums\Stack> stack()
 * @method static \Laravel\Roster\Enums\TestFramework|null testFramework()
 * @method static \Laravel\Roster\Support\EnumSet<\Laravel\Roster\Enums\BrowserTestFramework> browserTestFrameworks()
 * @method static \Laravel\Roster\Support\EnumSet<\Laravel\Roster\Enums\Frontend> frontend()
 * @method static \Laravel\Roster\Support\EnumSet<\Laravel\Roster\Enums\StarterKit> starterKit()
 * @method static \Laravel\Roster\Detectors\AgentsDetection agents()
 * @method static \Laravel\Roster\Support\EnumSet<\Laravel\Roster\Enums\Approach> approach()
 * @method static string json()
 *
 * @see RosterManager
 */
class Roster extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RosterManager::class;
    }
}
