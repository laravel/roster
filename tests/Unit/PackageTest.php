<?php

declare(strict_types=1);

use Laravel\Roster\Enums\PackageSource;
use Laravel\Roster\Package;

it('exposes name', function (): void {
    $package = new Package(
        name: 'pestphp/pest',
        version: '3.8.1',
        source: PackageSource::COMPOSER,
    );

    expect($package->name())->toBe('pestphp/pest');
});

it('matches on raw name', function (): void {
    $package = new Package('pestphp/pest', '3.8.1', PackageSource::COMPOSER);

    expect($package->matches('pestphp/pest'))->toBeTrue();
    expect($package->matches('pest'))->toBeFalse();
});

it('reports major version', function (): void {
    $package = new Package('vue', '3.4.0', PackageSource::NPM);

    expect($package->major())->toBe(3);
});

it('defaults to zero major when version is empty', function (): void {
    $package = new Package('foo', '', PackageSource::NPM);

    expect($package->major())->toBe(0);
});
