<?php

declare(strict_types=1);

namespace Laravel\Roster\Hosts;

use Laravel\Roster\Enums\JsPackageManager;
use Laravel\Roster\Support\InstalledSet;

class Js
{
    /**
     * @param  InstalledSet<JsPackageManager>  $packageManagers
     */
    public function __construct(protected InstalledSet $packageManagers) {}

    /** @return InstalledSet<JsPackageManager> */
    public function packageManagers(): InstalledSet
    {
        return $this->packageManagers;
    }
}
