<?php

declare(strict_types=1);

namespace Laravel\Roster;

use Laravel\Roster\Detectors\AgentsDetector;
use Laravel\Roster\Detectors\EditorsDetector;
use Laravel\Roster\Detectors\PackageManagersDetector;
use Laravel\Roster\Enums\Agent;
use Laravel\Roster\Enums\Editor;
use Laravel\Roster\Hosts\Js;
use Laravel\Roster\Support\InstalledSet;

class System
{
    /**
     * @param  InstalledSet<Agent>  $agents
     * @param  InstalledSet<Editor>  $editors
     */
    public function __construct(
        protected InstalledSet $agents,
        protected InstalledSet $editors,
        protected Js $js,
    ) {}

    /** @return InstalledSet<Agent> */
    public function agents(): InstalledSet
    {
        return $this->agents;
    }

    /** @return InstalledSet<Editor> */
    public function editors(): InstalledSet
    {
        return $this->editors;
    }

    public function js(): Js
    {
        return $this->js;
    }

    public static function scan(): self
    {
        return new self(
            new InstalledSet(AgentsDetector::installed()),
            new InstalledSet(EditorsDetector::installed()),
            new Js(new InstalledSet(PackageManagersDetector::installed())),
        );
    }

    /**
     * @return array{agents: array<int, string|int>, editors: array<int, string|int>, jsPackageManagers: array<int, string|int>}
     */
    public function toArray(): array
    {
        return [
            'agents' => $this->agents->values(),
            'editors' => $this->editors->values(),
            'jsPackageManagers' => $this->js->packageManagers()->values(),
        ];
    }

    public function json(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT) ?: '{}';
    }
}
