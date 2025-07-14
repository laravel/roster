<?php

use Laravel\Using\Approach;
use Laravel\Using\Enums\Approaches;
use Laravel\Using\Enums\Packages;
use Laravel\Using\Package;
use Laravel\Using\Using;

it('can add packages and approaches to using', function () {
    $package = new Package(Packages::PEST, '1.0.1');
    $approach = new Approach(Approaches::DDD);
    $using = new Using;
    $using->add($package);
    $using->add($approach);

    expect($using)->packages()->toArray()->toBe([$package]);
    expect($using)->approaches()->toArray()->toBe([$approach]);
});

it('knows if a package is in use', function () {
    $package = new Package(Packages::PEST, '1.0.1');
    $using = (new Using)->add($package);

    expect($using->uses(Packages::PEST))->toBeTrue();
    expect($using->uses(Packages::INERTIA))->toBeFalse();
});

it('knows if a specific version of a package is in use', function () {
    $usedPackage = new Package(Packages::PEST, '1.0.1');
    $using = (new Using)->add($usedPackage);

    expect($using->uses(Packages::PEST))->toBeTrue();
    expect($using->usesVersion(Packages::INERTIA, '1.0.1'))->toBeFalse();
    expect($using->usesVersion(Packages::PEST, '1.0.1'))->toBeTrue();

    expect($using->usesVersion(Packages::PEST, '1.0.1', '='))->toBeTrue();
    expect($using->usesVersion(Packages::PEST, '1.0.1', '=='))->toBeTrue();
    expect($using->usesVersion(Packages::PEST, '1.0.1', '>='))->toBeTrue();
    expect($using->usesVersion(Packages::PEST, '1.0.1', '<='))->toBeTrue();
    expect($using->usesVersion(Packages::PEST, '1.0.2', '<='))->toBeTrue();
    expect($using->usesVersion(Packages::PEST, '1.0.2', '!='))->toBeTrue();
    expect($using->usesVersion(Packages::PEST, '1.0.2', '<>'))->toBeTrue();

    expect($using->usesVersion(Packages::PEST, '1.0.2', '>='))->toBeFalse();
    expect($using->usesVersion(Packages::PEST, '1.0.0', '<='))->toBeFalse();
});

it('throws an exception with an invalid version when checking version usage', function () {
    (new Using)->usesVersion(Packages::PEST, '1.0.x', '##INVALID##');
})->throws(InvalidArgumentException::class);

it('throws an exception with an invalid operator when checking version usage', function () {
    (new Using)->usesVersion(Packages::PEST, '1.0.0', '##INVALID##');
})->throws(InvalidArgumentException::class);

it('knows if an approach is in use', function () {
    $approach = new Approach(Approaches::DDD);
    $using = (new Using)->add($approach);

    expect($using->uses(Approaches::DDD))->toBeTrue();
    expect($using->uses(Approaches::ACTION))->toBeFalse();
});

it('can return dev packages', function () {
    $devPackage = new Package(Packages::PEST, '1.0.1', true);
    $package = new Package(Packages::INERTIA, '2.0.0');
    $using = (new Using)->add($devPackage)->add($package);

    expect($using->uses(Packages::INERTIA))->toBeTrue();
    expect($using->uses(Packages::PEST))->toBeTrue();

    expect($using->devPackages()->toArray())->toBe([$devPackage]);
});
