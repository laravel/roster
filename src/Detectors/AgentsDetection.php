<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use Laravel\Roster\Contracts\ScopedDetection;
use Laravel\Roster\Enums\Agent;
use Laravel\Roster\Support\EnumSet;

class AgentsDetection implements ScopedDetection
{
    /** @var EnumSet<Agent> */
    protected EnumSet $configured;

    /** @var EnumSet<Agent> */
    protected EnumSet $installed;

    /**
     * @param  array<int, Agent>  $configured
     * @param  array<int, Agent>  $installed
     */
    public function __construct(array $configured, array $installed)
    {
        $this->configured = new EnumSet($configured);
        $this->installed = new EnumSet($installed);
    }

    /**
     * @return EnumSet<Agent>
     */
    public function configured(): EnumSet
    {
        return $this->configured;
    }

    /**
     * @return EnumSet<Agent>
     */
    public function installed(): EnumSet
    {
        return $this->installed;
    }

    public function isConfigured(Agent $agent): bool
    {
        return $this->configured->uses($agent);
    }

    public function isInstalled(Agent $agent): bool
    {
        return $this->installed->uses($agent);
    }
}
