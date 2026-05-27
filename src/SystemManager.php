<?php

declare(strict_types=1);

namespace Laravel\Roster;

use Laravel\Roster\Enums\Agent;
use Laravel\Roster\Enums\Editor;
use Laravel\Roster\Hosts\Js;
use Laravel\Roster\Support\CachesScan;
use Laravel\Roster\Support\InstalledSet;
use Laravel\Roster\Support\SystemProbe;

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

    /** @return InstalledSet<Editor> */
    public function editors(): InstalledSet
    {
        return $this->instance()->editors();
    }

    public function js(): Js
    {
        return $this->instance()->js();
    }

    public function json(): string
    {
        return $this->instance()->json();
    }

    protected function resetCachedInstance(): void
    {
        $this->cached = null;
        SystemProbe::resetCache();
    }
}
