<?php

use Laravel\Using\Enums\Packages;
use Laravel\Using\Package;
use Laravel\Using\Scanners\PackageLock;

it('scans valid package-lock.json', function () {
    $path = __DIR__.'/../../fixtures/fog/package-lock.json';
    $packageLock = new PackageLock($path);
    $items = $packageLock->scan();

    $tailwind = $items->first(fn (Package $package) => $package->package() === Packages::TAILWINDCSS);
    $inertiaReact = $items->first(fn (Package $package) => $package->package() === Packages::INERTIA_REACT);

    expect($tailwind->version())->toEqual('3.4.3');
    expect($inertiaReact)->toBeNull();

});
