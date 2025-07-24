<?php

use Laravel\Roster\Approach;
use Laravel\Roster\Enums\Approaches;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;
use Laravel\Roster\Roster;

it('can add packages and approaches to roster', function () {
    $package = new Package(Packages::PEST, 'pestphp/pest', '1.0.1');
    $approach = new Approach(Approaches::DDD);
    $roster = new Roster;
    $roster->add($package);
    $roster->add($approach);

    expect($roster)->packages()->toArray()->toBe([$package]);
    expect($roster)->approaches()->toArray()->toBe([$approach]);
});

it('knows if a package is in use', function () {
    $package = new Package(Packages::PEST, 'pestphp/pest', '1.0.1');
    $roster = (new Roster)->add($package);

    expect($roster->uses(Packages::PEST))->toBeTrue();
    expect($roster->uses(Packages::INERTIA))->toBeFalse();
});

it('knows if a specific version of a package is in use', function () {
    $usedPackage = new Package(Packages::PEST, 'pestphp/pest', '1.0.1');
    $roster = (new Roster)->add($usedPackage);

    expect($roster->uses(Packages::PEST))->toBeTrue();
    expect($roster->usesVersion(Packages::INERTIA, '1.0.1'))->toBeFalse();
    expect($roster->usesVersion(Packages::PEST, '1.0.1'))->toBeTrue();

    expect($roster->usesVersion(Packages::PEST, '1.0.1', '='))->toBeTrue();
    expect($roster->usesVersion(Packages::PEST, '1.0.1', '=='))->toBeTrue();
    expect($roster->usesVersion(Packages::PEST, '1.0.1', '>='))->toBeTrue();
    expect($roster->usesVersion(Packages::PEST, '1.0.1', '<='))->toBeTrue();
    expect($roster->usesVersion(Packages::PEST, '1.0.2', '<='))->toBeTrue();
    expect($roster->usesVersion(Packages::PEST, '1.0.2', '!='))->toBeTrue();
    expect($roster->usesVersion(Packages::PEST, '1.0.2', '<>'))->toBeTrue();

    expect($roster->usesVersion(Packages::PEST, '1.0.2', '>='))->toBeFalse();
    expect($roster->usesVersion(Packages::PEST, '1.0.0', '<='))->toBeFalse();
});

it('throws an exception with an invalid version when checking version usage', function () {
    (new Roster)->usesVersion(Packages::PEST, '1.0.x', '##INVALID##');
})->throws(InvalidArgumentException::class);

it('throws an exception with an invalid operator when checking version usage', function () {
    (new Roster)->usesVersion(Packages::PEST, '1.0.0', '##INVALID##');
})->throws(InvalidArgumentException::class);

it('knows if an approach is in use', function () {
    $approach = new Approach(Approaches::DDD);
    $roster = (new Roster)->add($approach);

    expect($roster->uses(Approaches::DDD))->toBeTrue();
    expect($roster->uses(Approaches::ACTION))->toBeFalse();
});

it('can return dev packages', function () {
    $devPackage = new Package(Packages::PEST, 'pestphp/pest', '1.0.1', true);
    $package = new Package(Packages::INERTIA, 'inertiajs/inertia-laravel', '2.0.0');
    $roster = (new Roster)->add($devPackage)->add($package);

    expect($roster->uses(Packages::INERTIA))->toBeTrue();
    expect($roster->uses(Packages::PEST))->toBeTrue();

    expect($roster->packages()->dev()->toArray())->toBe([$devPackage]);
});

it('can return a specific package', function () {
    $package = new Package(Packages::PEST, 'pestphp/pest', '1.0.1');
    $roster = (new Roster)->add($package);

    expect($roster->package(Packages::PEST))->toBe($package);
    expect($roster->package(Packages::INERTIA))->toBeNull();
});

it('can return raw package name', function () {
    $package = new Package(Packages::PEST, 'pestphp/pest', '1.0.1');

    expect($package->rawName())->toBe('pestphp/pest');
    expect($package->name())->toBe('PEST');
});
