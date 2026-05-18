<?php

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
        $configured = $committed !== null ? [$committed] : [];
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
