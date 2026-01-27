<?php

use Laravel\Roster\Approach;
use Laravel\Roster\Enums\Approaches;
use Laravel\Roster\Scanners\DirectoryStructure;

it('detects a modular approach when a lowercase modules directory exists', function () {
    $path = sys_get_temp_dir().'/roster_test_modular_lower_'.uniqid();
    $modulesPath = $path.DIRECTORY_SEPARATOR.'modules';

    mkdir($modulesPath, 0777, true);

    $items = (new DirectoryStructure($path))->scan();

    expect($items->contains(
        fn ($item) => $item instanceof Approach && $item->approach() === Approaches::MODULAR)
    )->toBeTrue();

    rmdir($modulesPath);
    rmdir($path);
});

it('detects a modular approach when the capitalized Modules directory exists', function () {
    $path = sys_get_temp_dir().'/roster_test_modular_caps_'.uniqid();
    $modulesPath = $path.DIRECTORY_SEPARATOR.'Modules';

    mkdir($modulesPath, 0777, true);

    $items = (new DirectoryStructure($path))->scan();

    expect($items->contains(
        fn ($item) => $item instanceof Approach && $item->approach() === Approaches::MODULAR)
    )->toBeTrue();

    rmdir($modulesPath);
    rmdir($path);
});
