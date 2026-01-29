<?php

use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Roster;

it('adds found composer packages to roster class', function () {
    $path = __DIR__.'/../fixtures/fog';

    $roster = Roster::scan($path);

    // Overall - 12 packages from composer (dusk, socialite, folio, volt, fluxui_free, laravel, pest, pint, filament, livewire, flux, phpunit, boost) and 2 from package lock (tailwind, alpine)
    expect($roster->packages())->toHaveCount(16);

    // From composer
    expect($roster->uses(Packages::PEST))->toBeTrue();
    expect($roster->uses(Packages::FOLIO))->toBeTrue();
    expect($roster->uses(Packages::VOLT))->toBeTrue();
    expect($roster->uses(Packages::FLUXUI_FREE))->toBeTrue();
    expect($roster->uses(Packages::PINT))->toBeTrue();
    expect($roster->uses(Packages::LARAVEL))->toBeTrue();
    expect($roster->uses(Packages::BOOST))->toBeTrue();
    expect($roster->uses(Packages::INERTIA))->toBeFalse();

    expect($roster->usesVersion(Packages::PEST, '3.8.1'))->toBeTrue();
    expect($roster->usesVersion(Packages::PINT, '1.21.2'))->toBeTrue();

    // From packagelock
    expect($roster->usesVersion(Packages::TAILWINDCSS, '3.4.16'))->toBeTrue();
    expect($roster->usesVersion(Packages::ALPINEJS, '3.14.7'))->toBeTrue();
});
