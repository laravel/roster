<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use Laravel\Roster\Ecosystems\JsEcosystem;
use Laravel\Roster\Enums\Frontend;
use Laravel\Roster\Support\EnumSet;

class FrontendDetector
{
    /** @var array<string, Frontend> */
    private const RULES = [
        'vue' => Frontend::VUE,
        'react' => Frontend::REACT,
        'svelte' => Frontend::SVELTE,
    ];

    /**
     * @return EnumSet<Frontend>
     */
    public function detect(JsEcosystem $js): EnumSet
    {
        /** @var array<int, Frontend> $found */
        $found = [];

        foreach (self::RULES as $package => $frontend) {
            if ($js->uses($package)) {
                $found[] = $frontend;
            }
        }

        return new EnumSet($found);
    }
}
