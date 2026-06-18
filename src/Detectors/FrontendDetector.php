<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use Laravel\Roster\Ecosystems\JsEcosystem;
use Laravel\Roster\Enums\Frontend;

class FrontendDetector
{
    /** @var array<string, Frontend> */
    private const RULES = [
        'vue' => Frontend::VUE,
        'react' => Frontend::REACT,
        'svelte' => Frontend::SVELTE,
    ];

    /**
     * @return list<Frontend>
     */
    public static function detect(JsEcosystem $js): array
    {
        $found = [];

        foreach (self::RULES as $package => $frontend) {
            if ($js->usesDirect($package)) {
                $found[] = $frontend;
            }
        }

        return $found;
    }
}
