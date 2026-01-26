<?php

use Laravel\Roster\Approach;
use Laravel\Roster\Enums\Approaches;
use Laravel\Roster\Scanners\DirectoryStructure;

it('detects modular approach when Modules directory exists', function () {
    $path = sys_get_temp_dir().'/roster_test_modular_'.uniqid();
    $modulesPath = $path.DIRECTORY_SEPARATOR.'modules';

    mkdir($modulesPath, 0777, true);

    $items = (new DirectoryStructure($path))->scan();

    $hasModular = $items->contains(function ($item) {
        return $item instanceof Approach && $item->approach() === Approaches::MODULAR;
    });

    expect($hasModular)->toBeTrue();

    rmdir($modulesPath);
    rmdir($path);
});
