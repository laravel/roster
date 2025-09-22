<?php

use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Scanners\Composer;

it('can parse installed packages', function () {
    $path = __DIR__.'/../../fixtures/fog/composer.lock';
    $uses = (new Composer($path))->scan();

    $laravel = $uses->first(fn ($item) => $item->package() === Packages::LARAVEL);
    expect($laravel->version())->toEqual('11.44.2');
    expect($laravel->isDev())->toBeFalse();
    expect($laravel->direct())->toBeTrue();
    expect($laravel->constraint())->toEqual('^11.0');

    $pest = $uses->first(fn ($item) => $item->package() === Packages::PEST);
    expect($pest->version())->toEqual('3.8.1');
    expect($pest->isDev())->toBeTrue();

    $pint = $uses->first(fn ($item) => $item->package() === Packages::PINT);
    expect($pint->version())->toEqual('1.21.2');
    expect($pint->isDev())->toBeFalse();

    $inertia = $uses->first(fn ($item) => $item->package() === Packages::INERTIA);
    expect($inertia)->toBeNull();
});

it('adds 2 entries for inertia', function () {
    $composerLockContent = '{
        "packages": [
            {
                "name": "inertiajs/inertia-laravel",
                "version": "v123.456.789"
            }
        ],
        "packages-dev": []
    }';

    $tempFile = tempnam(sys_get_temp_dir(), 'composer_lock_test');
    file_put_contents($tempFile, $composerLockContent);

    $uses = (new Composer($tempFile))->scan();

    unlink($tempFile);

    $laravel = $uses->first(fn ($item) => $item->package() === Packages::LARAVEL);
    expect($laravel)->toBeNull();

    // INERTIA is the general package - is it using inertia at all?
    $inertia = $uses->first(fn ($item) => $item->package() === Packages::INERTIA);
    expect($inertia->version())->toEqual('123.456.789');
    expect($inertia->isDev())->toBeFalse();

    // The specific package of Inertia
    $inertia = $uses->first(fn ($item) => $item->package() === Packages::INERTIA_LARAVEL);
    expect($inertia->version())->toEqual('123.456.789');
    expect($inertia->isDev())->toBeFalse();
});

it('detects PHPUnit from fixture', function () {
    $path = __DIR__.'/../../fixtures/phpunit/composer.lock';
    $uses = (new Composer($path))->scan();

    $phpunit = $uses->first(fn ($item) => $item->package() === Packages::PHPUNIT);
    expect($phpunit)->not()->toBeNull();
    expect($phpunit->version())->toEqual('11.4.3');
    expect($phpunit->isDev())->toBeTrue();
});
