<?php

declare(strict_types=1);

use Laravel\Roster\Enums\PackageSource;
use Laravel\Roster\Package;

it('reports major version', function (): void {
    $package = new Package('vue', '3.4.0', PackageSource::Npm);

    expect($package->major())->toBe(3);
});

it('reports null major when the version is unknown', function (): void {
    $package = new Package('foo', '', PackageSource::Npm);

    expect($package->major())->toBeNull();
});
