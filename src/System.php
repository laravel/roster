<?php

declare(strict_types=1);

namespace Laravel\Roster;

use Laravel\Roster\Detectors\AgentsDetector;
use Laravel\Roster\Detectors\PackageManagersDetector;
use Laravel\Roster\Enums\Agent;
use Laravel\Roster\Enums\JsPackageManager;
use Laravel\Roster\Support\InstalledSet;

class System
{
    /**
     * @param  InstalledSet<Agent>  $agents
     * @param  InstalledSet<JsPackageManager>  $packageManagers
     */
    public function __construct(
        protected InstalledSet $agents,
        protected InstalledSet $packageManagers,
    ) {}

    /** @return InstalledSet<Agent> */
    public function agents(): InstalledSet
    {
        return $this->agents;
    }

    /** @return InstalledSet<JsPackageManager> */
    public function packageManagers(): InstalledSet
    {
        return $this->packageManagers;
    }

    public static function scan(): self
    {
        return new self(
            new InstalledSet(AgentsDetector::installed()),
            new InstalledSet(PackageManagersDetector::installed()),
        );
    }

    /**
     * @return array{agents: array<int, string|int>, jsPackageManagers: array<int, string|int>}
     */
    public function toArray(): array
    {
        return [
            'agents' => $this->agents->values(),
            'jsPackageManagers' => $this->packageManagers->values(),
        ];
    }

    public function json(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT) ?: '{}';
    }
}
