<?php

declare(strict_types=1);

namespace Laravel\Roster\Ecosystems;

use Laravel\Roster\Detectors\PackageManagersDetection;
use Laravel\Roster\PackageCollection;

class JsEcosystem extends Ecosystem
{
    public function __construct(
        PackageCollection $packages,
        protected PackageManagersDetection $packageManagers,
    ) {
        parent::__construct($packages);
    }

    public function packageManagers(): PackageManagersDetection
    {
        return $this->packageManagers;
    }
}
