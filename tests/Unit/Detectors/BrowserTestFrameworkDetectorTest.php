<?php

declare(strict_types=1);

use Laravel\Roster\Detectors\BrowserTestFrameworkDetector;
use Laravel\Roster\Enums\BrowserTestFramework;

it('detects browser test frameworks across php and js', function (): void {
    $detection = (new BrowserTestFrameworkDetector)->detect(
        phpEcosystem(['laravel/dusk', 'pestphp/pest-plugin-browser']),
        jsEcosystem(['@playwright/test']),
    );

    expect($detection->uses(BrowserTestFramework::DUSK))->toBeTrue();
    expect($detection->uses(BrowserTestFramework::PEST_BROWSER))->toBeTrue();
    expect($detection->uses(BrowserTestFramework::PLAYWRIGHT))->toBeTrue();
    expect($detection->uses(BrowserTestFramework::CYPRESS))->toBeFalse();
    expect($detection->all())->toHaveCount(3);
});
