<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use Laravel\Roster\Enums\JsPackageManager;
use Laravel\Roster\Support\SystemProbe;

class PackageManagersDetector
{
    public function __construct(
        protected string $basePath,
        protected bool $detectSystem = true,
    ) {}

    public function detect(?JsPackageManager $committed): PackageManagersDetection
    {
        $configured = $committed instanceof \Laravel\Roster\Enums\JsPackageManager ? [$committed] : [];
        $installed = [];

        if ($this->detectSystem) {
            foreach (JsPackageManager::cases() as $manager) {
                if (SystemProbe::commandExists($manager->binary())) {
                    $installed[] = $manager;
                }
            }
        }

        return new PackageManagersDetection($configured, $installed);
    }
}
