<?php

use Laravel\Using\Approach;

it('can create instances of approaches', function () {
    $approach = new \Laravel\Using\Approach(\Laravel\Using\Enums\Approaches::DDD);
    expect($approach)->toBeInstanceOf(Approach::class);
});

it('can create instances of packages', function () {
    $approach = new \Laravel\Using\Package(\Laravel\Using\Enums\Packages::PEST, '1.0.1');
    expect($approach)->toBeInstanceOf(\Laravel\Using\Package::class);
});
