<?php

use Laravel\Using\Enums\Packages;
use Laravel\Using\Using;

it('adds found composer packages to using class', function () {
    $path = __DIR__ . '/../fixtures/fog';

    $using = Using::scan($path);

    // Overall - 6 packages from composer (laravel, pest, pint, filament, livewire, flux) and 2 from package lock (tailwind, alpine)
    expect($using->packages())->toHaveCount(8);

    // From composer
    expect($using->uses(Packages::PEST))->toBeTrue();
    expect($using->uses(Packages::PINT))->toBeTrue();
    expect($using->uses(Packages::LARAVEL))->toBeTrue();
    expect($using->uses(Packages::INERTIA))->toBeFalse();

    expect($using->usesVersion(Packages::PEST, '3.8.1'))->toBeTrue();
    expect($using->usesVersion(Packages::PINT, '1.21.2'))->toBeTrue();

    // From packagelock
    expect($using->usesVersion(Packages::TAILWINDCSS, '3.4.3'))->toBeTrue();
    expect($using->usesVersion(Packages::ALPINEJS, '3.4.2'))->toBeTrue();
});
