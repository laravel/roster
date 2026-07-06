<?php

declare(strict_types=1);

namespace Laravel\Roster;

use Laravel\Roster\Detectors\AgentsDetector;
use Laravel\Roster\Detectors\EditorsDetector;
use Laravel\Roster\Detectors\PackageManagersDetector;
use Laravel\Roster\Enums\Agent;
use Laravel\Roster\Enums\Editor;
use Laravel\Roster\Enums\JsPackageManager;
use Laravel\Roster\Support\EnumSet;

class System
{
    /**
     * @param  EnumSet<Agent>  $agents
     * @param  EnumSet<Editor>  $editors
     * @param  EnumSet<JsPackageManager>  $jsPackageManagers
     */
    public function __construct(
        protected EnumSet $agents,
        protected EnumSet $editors,
        protected EnumSet $jsPackageManagers,
    ) {}

    /** @return EnumSet<Agent> */
    public function agents(): EnumSet
    {
        return $this->agents;
    }

    /** @return EnumSet<Editor> */
    public function editors(): EnumSet
    {
        return $this->editors;
    }

    /** @return EnumSet<JsPackageManager> */
    public function jsPackageManagers(): EnumSet
    {
        return $this->jsPackageManagers;
    }

    public static function scan(): self
    {
        return new self(
            new EnumSet(AgentsDetector::installed()),
            new EnumSet(EditorsDetector::installed()),
            new EnumSet(PackageManagersDetector::installed()),
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
            'jsPackageManagers' => $this->jsPackageManagers->values(),
        ];
    }

    public function json(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT) ?: '{}';
    }
}
