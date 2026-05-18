<?php

namespace Laravel\Roster\Detectors;

use Laravel\Roster\Contracts\ScopedDetection;
use Laravel\Roster\Enums\JsPackageManager;
use Laravel\Roster\Support\EnumSet;

class PackageManagersDetection implements ScopedDetection
{
    /** @var EnumSet<JsPackageManager> */
    protected EnumSet $configured;

    /** @var EnumSet<JsPackageManager> */
    protected EnumSet $installed;

    /**
     * @param  array<int, JsPackageManager>  $configured
     * @param  array<int, JsPackageManager>  $installed
     */
    public function __construct(array $configured, array $installed)
    {
        $this->configured = new EnumSet($configured);
        $this->installed = new EnumSet($installed);
    }

    /**
     * @return EnumSet<JsPackageManager>
     */
    public function configured(): EnumSet
    {
        return $this->configured;
    }

    /**
     * @return EnumSet<JsPackageManager>
     */
    public function installed(): EnumSet
    {
        return $this->installed;
    }
}
