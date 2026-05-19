<?php

declare(strict_types=1);

namespace Laravel\Roster\Ecosystems;

use Laravel\Roster\Enums\JsPackageManager;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\Support\EnumSet;

class JsEcosystem extends Ecosystem
{
    /**
     * @param  EnumSet<JsPackageManager>  $packageManagers
     */
    public function __construct(
        PackageCollection $packages,
        protected EnumSet $packageManagers,
    ) {
        parent::__construct($packages);
    }

    /**
     * @return EnumSet<JsPackageManager>
     */
    public function packageManagers(): EnumSet
    {
        return $this->packageManagers;
    }
}
