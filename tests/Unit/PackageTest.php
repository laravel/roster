<?php

declare(strict_types=1);

use Laravel\Roster\Enums\PackageSource;
use Laravel\Roster\Package;

it('exposes name and alias', function (): void {
    $package = new Package(
        name: 'pestphp/pest',
        version: '3.8.1',
        source: PackageSource::COMPOSER,
        alias: 'pest',
    );

    expect($package->name())->toBe('pestphp/pest');
    expect($package->alias())->toBe('pest');
});

it('matches on raw name and alias', function (): void {
    $package = new Package('pestphp/pest', '3.8.1', PackageSource::COMPOSER, alias: 'pest');

    expect($package->matches('pest'))->toBeTrue();
    expect($package->matches('pestphp/pest'))->toBeTrue();
    expect($package->matches('phpunit'))->toBeFalse();
});

it('reports major version', function (): void {
    $package = new Package('vue', '3.4.0', PackageSource::NPM);

    expect($package->major())->toBe(3);
});

it('defaults to zero major when version is empty', function (): void {
    $package = new Package('foo', '', PackageSource::NPM);

    expect($package->major())->toBe(0);
});
