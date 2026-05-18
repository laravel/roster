<?php

namespace Laravel\Roster\Detectors;

use Laravel\Roster\Ecosystems\JsEcosystem;
use Laravel\Roster\Enums\Frontend;
use Laravel\Roster\Support\EnumSet;

class FrontendDetector
{
    /**
     * @return EnumSet<Frontend>
     */
    public function detect(JsEcosystem $js): EnumSet
    {
        /** @var array<int, Frontend> $found */
        $found = [];

        if ($js->uses('vue')) {
            $found[] = Frontend::VUE;
        }
        if ($js->uses('react')) {
            $found[] = Frontend::REACT;
        }
        if ($js->uses('svelte')) {
            $found[] = Frontend::SVELTE;
        }

        return new EnumSet($found);
    }
}
