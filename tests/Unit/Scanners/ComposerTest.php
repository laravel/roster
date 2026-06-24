<?php

use Laravel\Roster\Enums\PackageSource;
use Laravel\Roster\Scanners\Composer;

it('parses installed packages with raw names', function (): void {
    $path = __DIR__.'/../../fixtures/fog/composer.lock';
    $packages = (new Composer($path))->scan();

    $laravel = $packages->first(fn ($p): bool => $p->name() === 'laravel/framework');
    expect($laravel)->not->toBeNull();
    expect($laravel->version())->toEqual('11.44.2');
    expect($laravel->isDev())->toBeFalse();
    expect($laravel->isDirect())->toBeTrue();
    expect($laravel->constraint())->toEqual('^11.0');
    expect($laravel->source())->toBe(PackageSource::COMPOSER);
    expect($laravel->path())->toEndWith('vendor'.DIRECTORY_SEPARATOR.'laravel'.DIRECTORY_SEPARATOR.'framework');

    $pest = $packages->first(fn ($p): bool => $p->name() === 'pestphp/pest');
    expect($pest)->not->toBeNull();
    expect($pest->version())->toEqual('3.8.1');
    expect($pest->isDev())->toBeTrue();
});

it('strips composer version prefixes', function (): void {
    $composerLockContent = '{
        "packages": [
            {"name": "inertiajs/inertia-laravel", "version": "v2.0.5"}
        ],
        "packages-dev": []
    }';

    $tempFile = tempnam(sys_get_temp_dir(), 'composer_lock_test');
    file_put_contents($tempFile, $composerLockContent);

    $packages = (new Composer($tempFile))->scan();
    unlink($tempFile);

    $inertia = $packages->first(fn ($p): bool => $p->name() === 'inertiajs/inertia-laravel');
    expect($inertia)->not->toBeNull();
    expect($inertia->version())->toEqual('2.0.5');
});

it('respects composer vendor-dir config', function (): void {
    $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'roster_vendor_dir_test_'.uniqid();
    mkdir($tempDir, 0755, true);

    file_put_contents($tempDir.DIRECTORY_SEPARATOR.'composer.json', json_encode([
        'require' => ['laravel/framework' => '^11.0'],
        'config' => ['vendor-dir' => 'lib/packages'],
    ]));
    file_put_contents($tempDir.DIRECTORY_SEPARATOR.'composer.lock', json_encode([
        'packages' => [['name' => 'laravel/framework', 'version' => 'v11.0.0']],
        'packages-dev' => [],
    ]));

    $packages = (new Composer($tempDir.DIRECTORY_SEPARATOR.'composer.lock'))->scan();
    $laravel = $packages->first(fn ($p): bool => $p->name() === 'laravel/framework');

    expect($laravel->path())->toEndWith('lib'.DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.'laravel'.DIRECTORY_SEPARATOR.'framework');

    unlink($tempDir.DIRECTORY_SEPARATOR.'composer.json');
    unlink($tempDir.DIRECTORY_SEPARATOR.'composer.lock');
    rmdir($tempDir);
});

it('marks transitive dependencies as indirect', function (): void {
    $path = __DIR__.'/../../fixtures/fog/composer.lock';
    $packages = (new Composer($path))->scan();

    $livewire = $packages->first(fn ($p): bool => $p->name() === 'livewire/livewire');
    expect($livewire->isDirect())->toBeTrue();

    $prompts = $packages->first(fn ($p): bool => $p->name() === 'laravel/prompts');
    expect($prompts)->not->toBeNull();
    expect($prompts->isDirect())->toBeFalse();
});
