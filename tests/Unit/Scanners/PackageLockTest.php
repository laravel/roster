<?php

use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;
use Laravel\Roster\Scanners\PackageLock;

$packageLockPath = __DIR__.'/../../fixtures/fog/package-lock.json';
$pnpmLockPath = __DIR__.'/../../fixtures/fog/pnpm-lock.yaml';
$yarnV1LockPath = __DIR__.'/../../fixtures/fog/yarn-v1.lock';
$yarnLockPath = __DIR__.'/../../fixtures/fog/yarn.lock';
$tempPackagePath = $packageLockPath.'.bac';
$tempPnpmPath = $pnpmLockPath.'.bac';
$tempYarnV1Path = $yarnV1LockPath.'.bac';
$tempYarnPath = $yarnLockPath.'.bac';

afterEach(function () use ($packageLockPath, $pnpmLockPath, $yarnV1LockPath, $yarnLockPath, $tempPackagePath, $tempPnpmPath, $tempYarnV1Path, $tempYarnPath) {
    // Restore original files after each test
    if (file_exists($tempPackagePath)) {
        rename($tempPackagePath, $packageLockPath);
    }
    if (file_exists($tempPnpmPath)) {
        rename($tempPnpmPath, $pnpmLockPath);
    }
    if (file_exists($tempYarnV1Path)) {
        rename($tempYarnV1Path, $yarnV1LockPath);
    }
    if (file_exists($tempYarnPath)) {
        rename($tempYarnPath, $yarnLockPath);
    }
});

it('scans valid package-lock.json', function () {
    $path = __DIR__.'/../../fixtures/fog/';
    $packageLock = new PackageLock($path);
    $items = $packageLock->scan();

    $tailwind = $items->first(fn (Package $package) => $package->package() === Packages::TAILWINDCSS);
    $inertiaReact = $items->first(fn (Package $package) => $package->package() === Packages::INERTIA_REACT);

    expect($tailwind->version())->toEqual('3.4.16'); // Installed version, not dependency constraint
    expect($inertiaReact)->toBeNull();
});

it('scans valid pnpm-lock.yaml', function () use ($packageLockPath, $tempPackagePath) {
    // Remove package-lock.json temporarily to test pnpm priority
    if (file_exists($packageLockPath)) {
        rename($packageLockPath, $tempPackagePath);
    }

    $path = __DIR__.'/../../fixtures/fog/';
    $packageLock = new PackageLock($path);
    $items = $packageLock->scan();

    $tailwind = $items->first(fn (Package $package) => $package->package() === Packages::TAILWINDCSS);
    $alpine = $items->first(fn (Package $package) => $package->package() === Packages::ALPINEJS);

    expect($tailwind->version())->toEqual('3.4.3');
    expect($alpine->version())->toEqual('3.4.2');

    // Restore package-lock.json
    if (file_exists($tempPackagePath)) {
        rename($tempPackagePath, $packageLockPath);
    }
});

it('scans valid yarn.lock v1', function () use ($yarnV1LockPath, $yarnLockPath, $packageLockPath, $pnpmLockPath, $tempPackagePath, $tempPnpmPath, $tempYarnPath, $tempYarnV1Path) {
    // Remove package-lock.json, pnpm-lock.yaml, and yarn.lock (v4) temporarily to test yarn v1 priority
    if (file_exists($packageLockPath)) {
        rename($packageLockPath, $tempPackagePath);
    }
    if (file_exists($pnpmLockPath)) {
        rename($pnpmLockPath, $tempPnpmPath);
    }
    if (file_exists($yarnLockPath)) {
        rename($yarnLockPath, $tempYarnPath);
    }

    // Backup yarn-v1.lock so afterEach can restore it if the test fails
    copy($yarnV1LockPath, $tempYarnV1Path);

    // Use yarn-v1.lock as yarn.lock for this test
    rename($yarnV1LockPath, $yarnLockPath);

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

    expect($tailwind->version())->toEqual('3.4.16')
        ->and($alpine->version())->toEqual('3.4.4');

    // Cleanup: delete the swapped yarn.lock so afterEach can restore originals
    unlink($yarnLockPath);
});

it('scans valid yarn.lock', function () use ($packageLockPath, $pnpmLockPath, $yarnV1LockPath, $tempPackagePath, $tempPnpmPath, $tempYarnV1Path) {
    // Remove package-lock.json, pnpm-lock.yaml, and yarn-v1.lock temporarily to test yarn v4 priority
    if (file_exists($packageLockPath)) {
        rename($packageLockPath, $tempPackagePath);
    }
    if (file_exists($pnpmLockPath)) {
        rename($pnpmLockPath, $tempPnpmPath);
    }
    if (file_exists($yarnV1LockPath)) {
        rename($yarnV1LockPath, $tempYarnV1Path);
    }

    $path = __DIR__.'/../../fixtures/fog/';
    $packageLock = new PackageLock($path);
    $items = $packageLock->scan();

    /** @var Package $tailwind */
    $tailwind = $items->first(
        fn ($item) => $item instanceof Package && $item->package() === Packages::TAILWINDCSS
    );

    /** @var Package $inertia */
    $inertia = $items->first(
        fn ($item) => $item instanceof Package && $item->package() === Packages::INERTIA_VUE
    );

    expect($tailwind->version())->toEqual('4.1.16')
        ->and($inertia->version())->toEqual('2.2.15');

    // Restore files
    if (file_exists($tempPackagePath)) {
        rename($tempPackagePath, $packageLockPath);
    }
    if (file_exists($tempPnpmPath)) {
        rename($tempPnpmPath, $pnpmLockPath);
    }
    if (file_exists($tempYarnV1Path)) {
        rename($tempYarnV1Path, $yarnV1LockPath);
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

it('scans valid bun.lock', function () use ($packageLockPath, $pnpmLockPath, $yarnLockPath, $yarnV1LockPath, $tempPackagePath, $tempPnpmPath, $tempYarnPath, $tempYarnV1Path) {
    // Remove other lock files temporarily to test bun priority
    if (file_exists($packageLockPath)) {
        rename($packageLockPath, $tempPackagePath);
    }
    if (file_exists($pnpmLockPath)) {
        rename($pnpmLockPath, $tempPnpmPath);
    }
    if (file_exists($yarnLockPath)) {
        rename($yarnLockPath, $tempYarnPath);
    }
    if (file_exists($yarnV1LockPath)) {
        rename($yarnV1LockPath, $tempYarnV1Path);
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
        ->and($alpine->version())->toEqual('3.4.2')
        ->and($alpine->isDev())->toBeTrue()
        ->and($tailwind->isDev())->toBeFalse();

    // Restore files
    if (file_exists($tempPackagePath)) {
        rename($tempPackagePath, $packageLockPath);
    }
    if (file_exists($tempPnpmPath)) {
        rename($tempPnpmPath, $pnpmLockPath);
    }
    if (file_exists($tempYarnPath)) {
        rename($tempYarnPath, $yarnLockPath);
    }
    if (file_exists($tempYarnV1Path)) {
        rename($tempYarnV1Path, $yarnV1LockPath);
    }
});
