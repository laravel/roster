<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use Laravel\Roster\Ecosystems\JsEcosystem;
use Laravel\Roster\Ecosystems\PhpEcosystem;
use Laravel\Roster\Enums\BrowserTestFramework;
use Laravel\Roster\Enums\PackageSource;

class BrowserTestFrameworkDetector
{
    /**
     * @var list<array{framework: BrowserTestFramework, ecosystem: PackageSource, package: string, markers: list<string>}>
     */
    private const RULES = [
        [
            'framework' => BrowserTestFramework::DUSK,
            'ecosystem' => PackageSource::COMPOSER,
            'package' => 'laravel/dusk',
            'markers' => ['tests/Browser'],
        ],
        [
            'framework' => BrowserTestFramework::PEST_BROWSER,
            'ecosystem' => PackageSource::COMPOSER,
            'package' => 'pestphp/pest-plugin-browser',
            'markers' => [],
        ],
        [
            'framework' => BrowserTestFramework::PLAYWRIGHT,
            'ecosystem' => PackageSource::NPM,
            'package' => '@playwright/test',
            'markers' => ['playwright.config.ts', 'playwright.config.js', 'playwright.config.mjs', 'playwright.config.cjs'],
        ],
        [
            'framework' => BrowserTestFramework::CYPRESS,
            'ecosystem' => PackageSource::NPM,
            'package' => 'cypress',
            'markers' => ['cypress.config.ts', 'cypress.config.js', 'cypress.config.mjs', 'cypress.config.cjs', 'cypress.json'],
        ],
    ];

    /**
     * @return list<BrowserTestFramework>
     */
    public static function detect(PhpEcosystem $php, JsEcosystem $js, string $basePath): array
    {
        $found = [];

        foreach (self::RULES as $rule) {
            $ecosystem = $rule['ecosystem'] === PackageSource::COMPOSER ? $php : $js;

            if ($ecosystem->usesDirect($rule['package']) && self::markersPresent($basePath, $rule['markers'])) {
                $found[] = $rule['framework'];
            }
        }

        return $found;
    }

    /**
     * @return list<string>
     */
    public static function markerPaths(): array
    {
        return array_merge(...array_column(self::RULES, 'markers'));
    }

    /**
     * @param  list<string>  $markers
     */
    private static function markersPresent(string $basePath, array $markers): bool
    {
        if ($markers === []) {
            return true;
        }

        foreach ($markers as $marker) {
            if (file_exists($basePath.str_replace('/', DIRECTORY_SEPARATOR, $marker))) {
                return true;
            }
        }

        return false;
    }
}
