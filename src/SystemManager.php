<?php

declare(strict_types=1);

namespace Laravel\Roster;

use Laravel\Roster\Enums\Agent;
use Laravel\Roster\Enums\JsPackageManager;
use Laravel\Roster\Support\CachesScan;
use Laravel\Roster\Support\InstalledSet;

class SystemManager
{
    use CachesScan;

    protected ?System $cached = null;

    public function scan(): System
    {
        return $this->cached = $this->rememberScan(
            'roster:system',
            fn (): System => System::scan(),
            System::class,
        );
    }

    public function instance(): System
    {
        return $this->cached ??= $this->scan();
    }

    /** @return InstalledSet<Agent> */
    public function agents(): InstalledSet
    {
        return $this->instance()->agents();
    }

    /** @return InstalledSet<JsPackageManager> */
    public function packageManagers(): InstalledSet
    {
        return $this->instance()->packageManagers();
    }

    public function json(): string
    {
        return $this->instance()->json();
    }

    protected function resetCachedInstance(): void
    {
        $this->cached = null;
    }
}
