<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use Laravel\Roster\Enums\JsPackageManager;
use Laravel\Roster\Support\SystemProbe;

class PackageManagersDetector
{
    /**
     * @return list<JsPackageManager>
     */
    public static function installed(): array
    {
        $installed = [];

        foreach (JsPackageManager::cases() as $manager) {
            if (SystemProbe::commandExists($manager->value)) {
                $installed[] = $manager;
            }
        }

        return $installed;
    }
}
