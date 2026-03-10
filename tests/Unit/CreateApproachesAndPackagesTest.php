<?php

use Laravel\Roster\Approach;
use Laravel\Roster\Enums\Approaches;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;

it('can create instances of approaches', function () {
    $approach = new Approach(Approaches::DDD);
    expect($approach)->toBeInstanceOf(Approach::class);
});

it('can create instances of packages', function () {
    $approach = new Package(Packages::PEST, 'pestphp/pest', '1.0.1');
    expect($approach)->toBeInstanceOf(Package::class);
});
