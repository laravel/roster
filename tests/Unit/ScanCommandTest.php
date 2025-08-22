<?php

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

uses(TestCase::class);

/**
 * Remove the summary line from the command output
 * to allow existing tests to pass
 */
function clearSummaryFromOutput($output): string
{
    return implode("\n", array_slice(explode("\n", $output), 0, -2));
}

it('outputs JSON for directory with packages', function () {
    $path = __DIR__.'/../fixtures/fog';

    Artisan::call('roster:scan', ['directory' => $path]);

    $output = clearSummaryFromOutput(Artisan::output());
    $decoded = json_decode($output, true);

    expect($decoded)->toBeArray();
    expect($decoded)->toHaveKey('packages');
    expect($decoded['packages'])->toBeArray();
    expect(count($decoded['packages']))->toBeGreaterThan(0);
});

it('outputs empty JSON for empty directory', function () {
    $emptyDir = sys_get_temp_dir().'/roster_test_empty_'.uniqid();
    mkdir($emptyDir);

    Artisan::call('roster:scan', ['directory' => $emptyDir]);

    $output = Artisan::output();
    $decoded = json_decode($output, true);

    expect($decoded)->toBeArray();
    expect($decoded)->toHaveKey('packages');
    expect($decoded['packages'])->toBeEmpty();

    rmdir($emptyDir);
});

it('returns failure for non-existent directory', function () {
    $nonExistentDir = '/non/existent/directory';

    $exitCode = Artisan::call('roster:scan', ['directory' => $nonExistentDir]);

    expect($exitCode)->toBe(1);
});

it('returns failure for unreadable directory', function () {
    $invalidArg = 123;

    $exitCode = Artisan::call('roster:scan', ['directory' => $invalidArg]);

    expect($exitCode)->toBe(1);
});

it('returns the summary output', function () {
    $path = __DIR__.'/../fixtures/fog';

    Artisan::call('roster:scan', ['directory' => $path]);

    $output = Artisan::output();
    $summary = implode("\n", array_slice(explode("\n", $output), -2, 1));

    expect(str_contains($summary, 'Package'))->toBe(true);
});
