<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use Laravel\Roster\Ecosystems\JsEcosystem;
use Laravel\Roster\Ecosystems\PhpEcosystem;
use Laravel\Roster\Enums\BrowserTestFramework;
use Laravel\Roster\Enums\PackageSource;
use Laravel\Roster\Support\EnumSet;

class BrowserTestFrameworkDetector
{
    /** @var list<array{framework: BrowserTestFramework, ecosystem: PackageSource, package: string}> */
    private const RULES = [
        ['framework' => BrowserTestFramework::DUSK, 'ecosystem' => PackageSource::COMPOSER, 'package' => 'laravel/dusk'],
        ['framework' => BrowserTestFramework::PEST_BROWSER, 'ecosystem' => PackageSource::COMPOSER, 'package' => 'pestphp/pest-plugin-browser'],
        ['framework' => BrowserTestFramework::PLAYWRIGHT, 'ecosystem' => PackageSource::NPM, 'package' => '@playwright/test'],
        ['framework' => BrowserTestFramework::CYPRESS, 'ecosystem' => PackageSource::NPM, 'package' => 'cypress'],
    ];

    /**
     * @return EnumSet<BrowserTestFramework>
     */
    public function detect(PhpEcosystem $php, JsEcosystem $js): EnumSet
    {
        /** @var array<int, BrowserTestFramework> $found */
        $found = [];

        foreach (self::RULES as $rule) {
            $ecosystem = $rule['ecosystem'] === PackageSource::COMPOSER ? $php : $js;
            if ($ecosystem->uses($rule['package'])) {
                $found[] = $rule['framework'];
            }
        }

        return new EnumSet($found);
    }
}
