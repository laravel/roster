<?php

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

uses(TestCase::class);

it('outputs JSON for directory with packages', function () {
    $path = __DIR__.'/../fixtures/fog';

    Artisan::call('roster:scan', ['directory' => $path, '--no-system' => true]);

    $output = Artisan::output();
    $decoded = json_decode($output, true);

    expect($decoded)->toBeArray();
    expect($decoded)->toHaveKey('php');
    expect($decoded)->toHaveKey('js');
    expect(count($decoded['php']))->toBeGreaterThan(0);
});

it('outputs empty JSON for empty directory', function () {
    $emptyDir = sys_get_temp_dir().'/roster_test_empty_'.uniqid();
    mkdir($emptyDir);

    Artisan::call('roster:scan', ['directory' => $emptyDir, '--no-system' => true]);

    $output = Artisan::output();
    $decoded = json_decode($output, true);

    expect($decoded)->toBeArray();
    expect($decoded['php'])->toBe([]);
    expect($decoded['js'])->toBe([]);

    rmdir($emptyDir);
});

it('returns failure for non-existent directory', function () {
    $exitCode = Artisan::call('roster:scan', ['directory' => '/non/existent/directory']);
    expect($exitCode)->toBe(1);
});
