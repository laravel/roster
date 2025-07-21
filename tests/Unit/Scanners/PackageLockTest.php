<?php

use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;
use Laravel\Roster\Scanners\PackageLock;

it('scans valid package-lock.json', function () {
    $path = __DIR__.'/../../fixtures/fog/';
    $packageLock = new PackageLock($path);
    $items = $packageLock->scan();

    $tailwind = $items->first(fn (Package $package) => $package->package() === Packages::TAILWINDCSS);
    $inertiaReact = $items->first(fn (Package $package) => $package->package() === Packages::INERTIA_REACT);

    expect($tailwind->version())->toEqual('3.4.3');
    expect($inertiaReact)->toBeNull();
});

it('scans valid pnpm-lock.yaml', function () {
    // Remove package-lock.json temporarily to test pnpm priority
    $packageLockPath = __DIR__.'/../../fixtures/fog/package-lock.json';
    $tempPath = __DIR__.'/../../fixtures/fog/package-lock.json';

    if (file_exists($packageLockPath)) {
        rename($packageLockPath, $tempPath);
    }

    $path = __DIR__.'/../../fixtures/fog/';
    $packageLock = new PackageLock($path);
    $items = $packageLock->scan();

    $tailwind = $items->first(fn (Package $package) => $package->package() === Packages::TAILWINDCSS);
    $alpine = $items->first(fn (Package $package) => $package->package() === Packages::ALPINEJS);

    expect($tailwind->version())->toEqual('3.4.3');
    expect($alpine->version())->toEqual('3.4.2');

    // Restore package-lock.json
    if (file_exists($tempPath)) {
        rename($tempPath, $packageLockPath);
    }
});

it('scans valid yarn.lock', function () {
    // Remove package-lock.json and pnpm-lock.yaml temporarily to test yarn priority
    $packageLockPath = __DIR__.'/../../fixtures/fog/package-lock.json';
    $pnpmLockPath = __DIR__.'/../../fixtures/fog/pnpm-lock.yaml';
    $tempPackagePath = __DIR__.'/../../fixtures/fog/package-lock.json';
    $tempPnpmPath = __DIR__.'/../../fixtures/fog/pnpm-lock.yaml.bak';

    if (file_exists($packageLockPath)) {
        rename($packageLockPath, $tempPackagePath);
    }
    if (file_exists($pnpmLockPath)) {
        rename($pnpmLockPath, $tempPnpmPath);
    }

    $path = __DIR__.'/../../fixtures/fog/';
    $packageLock = new PackageLock($path);
    $items = $packageLock->scan();

    /** @var Package $tailwind */
    $tailwind = $items->first(
        fn ($item) => $item instanceof Package && $item->package() === Packages::TAILWINDCSS
    );

    /** @var Package $alpine */
    $alpine = $items->first(
        fn ($item) => $item instanceof Package && $item->package() === Packages::ALPINEJS
    );

    expect($tailwind->version())->toEqual('3.4.3')
        ->and($alpine->version())->toEqual('3.4.2');

    // Restore files
    if (file_exists($tempPackagePath)) {
        rename($tempPackagePath, $packageLockPath);
    }
    if (file_exists($tempPnpmPath)) {
        rename($tempPnpmPath, $pnpmLockPath);
    }
});

it('handles missing lock files gracefully', function () {
    $path = __DIR__.'/../../fixtures/empty/';

    // Create empty directory if it doesn't exist
    if (! is_dir($path)) {
        mkdir($path, 0755, true);
    }

    $packageLock = new PackageLock($path);
    $items = $packageLock->scan();

    expect($items)->toBeEmpty();
});
