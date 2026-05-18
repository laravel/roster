<?php

namespace Laravel\Roster\Detectors;

use Laravel\Roster\Ecosystems\JsEcosystem;
use Laravel\Roster\Ecosystems\PhpEcosystem;
use Laravel\Roster\Enums\BrowserTestFramework;
use Laravel\Roster\Support\EnumSet;

class BrowserTestFrameworkDetector
{
    /**
     * @return EnumSet<BrowserTestFramework>
     */
    public function detect(PhpEcosystem $php, JsEcosystem $js): EnumSet
    {
        /** @var array<int, BrowserTestFramework> $found */
        $found = [];

        if ($php->uses('laravel/dusk')) {
            $found[] = BrowserTestFramework::DUSK;
        }

        if ($php->uses('pestphp/pest-plugin-browser')) {
            $found[] = BrowserTestFramework::PEST_BROWSER;
        }

        if ($js->uses('@playwright/test')) {
            $found[] = BrowserTestFramework::PLAYWRIGHT;
        }

        if ($js->uses('cypress')) {
            $found[] = BrowserTestFramework::CYPRESS;
        }

        return new EnumSet($found);
    }
}
