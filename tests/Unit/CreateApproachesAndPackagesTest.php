<?php

use Laravel\Roster\Approach;

it('can create instances of approaches', function () {
    $approach = new \Laravel\Roster\Approach(\Laravel\Roster\Enums\Approaches::DDD);
    expect($approach)->toBeInstanceOf(Approach::class);
});

it('can create instances of packages', function () {
    $approach = new \Laravel\Roster\Package(\Laravel\Roster\Enums\Packages::PEST, '1.0.1');
    expect($approach)->toBeInstanceOf(\Laravel\Roster\Package::class);
});
