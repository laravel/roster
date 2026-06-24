<?php

declare(strict_types=1);

use Laravel\Roster\Detectors\ApproachDetector;
use Laravel\Roster\Enums\Approach;

it('detects action approach from app/Actions directory', function (): void {
    $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'roster_approach_'.uniqid().DIRECTORY_SEPARATOR;
    mkdir($base.'app'.DIRECTORY_SEPARATOR.'Actions', 0777, true);

    expect(ApproachDetector::detect($base))->toContain(Approach::ACTION);

    rmdir($base.'app'.DIRECTORY_SEPARATOR.'Actions');
    rmdir($base.'app');
    rmdir($base);
});

it('detects modular approach from modules / Modules / app-modules', function (): void {
    foreach (['modules', 'Modules', 'app-modules'] as $dir) {
        $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'roster_modular_'.uniqid().DIRECTORY_SEPARATOR;
        mkdir($base.$dir, 0777, true);

        expect(ApproachDetector::detect($base))->toContain(Approach::MODULAR);

        rmdir($base.$dir);
        rmdir($base);
    }
});

it('returns empty detection on a bare directory', function (): void {
    $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'roster_empty_'.uniqid().DIRECTORY_SEPARATOR;
    mkdir($base);

    expect(ApproachDetector::detect($base))->toBe([]);

    rmdir($base);
});
