<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

uses(TestCase::class);

it('outputs JSON for directory with packages', function (): void {
    $path = __DIR__.'/../fixtures/fog';

    Artisan::call('roster:scan', ['directory' => $path]);

    $output = Artisan::output();
    $decoded = json_decode($output, true);

    expect($decoded)->toBeArray();
    expect($decoded)->toHaveKey('php');
    expect($decoded)->toHaveKey('js');
    expect(count($decoded['php']))->toBeGreaterThan(0);
});

it('outputs empty JSON for empty directory', function (): void {
    $emptyDir = tempBase();

    Artisan::call('roster:scan', ['directory' => $emptyDir]);

    $output = Artisan::output();
    $decoded = json_decode($output, true);

    expect($decoded)->toBeArray();
    expect($decoded['php'])->toBe([]);
    expect($decoded['js'])->toBe([]);

    cleanup($emptyDir);
});

it('includes approaches only when requested', function (): void {
    $path = __DIR__.'/../fixtures/approaches/fillable-models-app';

    Artisan::call('roster:scan', ['directory' => $path]);
    $decoded = json_decode(Artisan::output(), true);

    expect($decoded)->not->toHaveKey('approaches');

    Artisan::call('roster:scan', ['directory' => $path, '--approaches' => true]);
    $decoded = json_decode(Artisan::output(), true);

    expect($decoded)->toHaveKey('approaches');
    expect($decoded['approaches'])->toHaveKey('mass-assignment-fillable');
    expect($decoded['approaches']['mass-assignment-fillable']['matched'])->toBeGreaterThanOrEqual(5);
});

it('defaults to the application base path when no directory is given', function (): void {
    $exitCode = Artisan::call('roster:scan');
    $decoded = json_decode(Artisan::output(), true);

    expect($exitCode)->toBe(0);
    expect($decoded)->toBeArray();
    expect($decoded)->toHaveKey('php');
});

it('returns failure for non-existent directory', function (): void {
    $exitCode = Artisan::call('roster:scan', ['directory' => '/non/existent/directory']);
    expect($exitCode)->toBe(1);
});

it('encodes a whole confidence as a float so the field keeps one json type', function (): void {
    Artisan::call('roster:scan', [
        'directory' => __DIR__.'/../fixtures/approaches/fillable-models-app',
        '--approaches' => true,
    ]);

    $output = Artisan::output();

    expect($output)->toContain('"confidence": 1.0')
        ->and($output)->not->toContain('"confidence": 1,');
});
