<?php

declare(strict_types=1);

namespace Laravel\Roster;

use Laravel\Roster\Enums\Agent;
use Laravel\Roster\Enums\Editor;
use Laravel\Roster\Enums\JsPackageManager;
use Laravel\Roster\Support\CachesScan;
use Laravel\Roster\Support\EnumSet;

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

    /** @return EnumSet<Agent> */
    public function agents(): EnumSet
    {
        return $this->instance()->agents();
    }

    /** @return EnumSet<Editor> */
    public function editors(): EnumSet
    {
        return $this->instance()->editors();
    }

    /** @return EnumSet<JsPackageManager> */
    public function jsPackageManagers(): EnumSet
    {
        return $this->instance()->jsPackageManagers();
    }

    public function json(): string
    {
        return $this->instance()->json();
    }
}
