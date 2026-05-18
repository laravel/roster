<?php

use Laravel\Roster\Detectors\BrowserTestFrameworkDetector;
use Laravel\Roster\Detectors\TestFrameworkDetector;
use Laravel\Roster\Enums\BrowserTestFramework;
use Laravel\Roster\Enums\TestFramework;

it('prefers pest over phpunit when both present', function () {
    $tf = (new TestFrameworkDetector)->detect(phpEcosystem(['pestphp/pest', 'phpunit/phpunit']));
    expect($tf)->toBe(TestFramework::PEST);
    expect($tf->is(TestFramework::PEST))->toBeTrue();
    expect($tf->is([TestFramework::PEST, TestFramework::PHPUNIT]))->toBeTrue();
    expect($tf->is(TestFramework::PHPUNIT))->toBeFalse();
});

it('returns phpunit when only phpunit present', function () {
    $tf = (new TestFrameworkDetector)->detect(phpEcosystem(['phpunit/phpunit']));
    expect($tf)->toBe(TestFramework::PHPUNIT);
});

it('returns null when no test framework present', function () {
    $tf = (new TestFrameworkDetector)->detect(phpEcosystem([]));
    expect($tf)->toBeNull();
});

it('detects browser test frameworks across php and js', function () {
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
