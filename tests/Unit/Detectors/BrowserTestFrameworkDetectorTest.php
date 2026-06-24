<?php

declare(strict_types=1);

use Laravel\Roster\Detectors\BrowserTestFrameworkDetector;
use Laravel\Roster\Enums\BrowserTestFramework;

it('detects a framework only when its package and marker are both present', function (): void {
    $base = tempBase();
    mkdir($base.'tests/Browser', 0777, true);          // Dusk marker
    touchFile($base.'playwright.config.ts');           // Playwright marker

    $found = BrowserTestFrameworkDetector::detect(
        phpEcosystem(['laravel/dusk', 'pestphp/pest-plugin-browser']),
        jsEcosystem(['@playwright/test', 'cypress']),
        $base,
    );

    expect($found)->toContain(BrowserTestFramework::DUSK);          // package + tests/Browser
    expect($found)->toContain(BrowserTestFramework::PEST_BROWSER);  // package alone is enough
    expect($found)->toContain(BrowserTestFramework::PLAYWRIGHT);    // package + config
    expect($found)->not->toContain(BrowserTestFramework::CYPRESS);  // package present, but no config
    expect($found)->toHaveCount(3);

    cleanup($base);
});

it('ignores a package with no confirming marker', function (): void {
    $base = tempBase();

    $found = BrowserTestFrameworkDetector::detect(
        phpEcosystem(['laravel/dusk']),
        jsEcosystem(['@playwright/test']),
        $base,
    );

    expect($found)->toBe([]);

    cleanup($base);
});

it('ignores a transitive package even when its config is present', function (): void {
    $base = tempBase();
    touchFile($base.'playwright.config.ts');

    $found = BrowserTestFrameworkDetector::detect(
        phpEcosystem([]),
        jsEcosystem([['name' => '@playwright/test', 'direct' => false]]),
        $base,
    );

    expect($found)->toBe([]);

    cleanup($base);
});
