<?php

declare(strict_types=1);

use Laravel\Roster\Enums\PackageSource;
use Laravel\Roster\Scanners\ComposerLock;

it('parses installed packages with raw names', function (): void {
    $packages = (new ComposerLock(__DIR__.'/../../fixtures/fog/'))->scan();

    $laravel = $packages->first(fn ($p): bool => $p->name() === 'laravel/framework');
    expect($laravel)->not->toBeNull();
    expect($laravel->version())->toEqual('11.44.2');
    expect($laravel->isDev())->toBeFalse();
    expect($laravel->isDirect())->toBeTrue();
    expect($laravel->constraint())->toEqual('^11.0');
    expect($laravel->source())->toBe(PackageSource::Composer);
    expect($laravel->path())->toEndWith('vendor'.DIRECTORY_SEPARATOR.'laravel'.DIRECTORY_SEPARATOR.'framework');

    $pest = $packages->first(fn ($p): bool => $p->name() === 'pestphp/pest');
    expect($pest)->not->toBeNull();
    expect($pest->version())->toEqual('3.8.1');
    expect($pest->isDev())->toBeTrue();
});

it('strips composer version prefixes', function (): void {
    $base = tempBase();

    file_put_contents($base.'composer.lock', json_encode([
        'packages' => [
            ['name' => 'inertiajs/inertia-laravel', 'version' => 'v2.0.5'],
        ],
        'packages-dev' => [],
    ]));

    $packages = (new ComposerLock($base))->scan();

    $inertia = $packages->first(fn ($p): bool => $p->name() === 'inertiajs/inertia-laravel');
    expect($inertia)->not->toBeNull();
    expect($inertia->version())->toEqual('2.0.5');

    cleanup($base);
});

it('respects composer vendor-dir config', function (): void {
    $base = tempBase();

    file_put_contents($base.'composer.json', json_encode([
        'require' => ['laravel/framework' => '^11.0'],
        'config' => ['vendor-dir' => 'lib/packages'],
    ]));
    file_put_contents($base.'composer.lock', json_encode([
        'packages' => [['name' => 'laravel/framework', 'version' => 'v11.0.0']],
        'packages-dev' => [],
    ]));

    $packages = (new ComposerLock($base))->scan();
    $laravel = $packages->first(fn ($p): bool => $p->name() === 'laravel/framework');

    expect($laravel->path())->toEndWith('lib'.DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.'laravel'.DIRECTORY_SEPARATOR.'framework');

    cleanup($base);
});

it('marks transitive dependencies as indirect', function (): void {
    $packages = (new ComposerLock(__DIR__.'/../../fixtures/fog/'))->scan();

    $livewire = $packages->first(fn ($p): bool => $p->name() === 'livewire/livewire');
    expect($livewire->isDirect())->toBeTrue();

    $prompts = $packages->first(fn ($p): bool => $p->name() === 'laravel/prompts');
    expect($prompts)->not->toBeNull();
    expect($prompts->isDirect())->toBeFalse();
});

it('returns an empty collection for a malformed composer.lock', function (): void {
    $base = tempBase();
    file_put_contents($base.'composer.lock', '{truncated');

    expect((new ComposerLock($base))->scan())->toHaveCount(0);

    cleanup($base);
});

it('classifies dev from the lockfile section even when require-dev disagrees', function (): void {
    $base = tempBase();

    file_put_contents($base.'composer.lock', json_encode([
        'packages' => [
            ['name' => 'symfony/var-dumper', 'version' => 'v7.2.0'],
        ],
        'packages-dev' => [],
    ]));
    file_put_contents($base.'composer.json', json_encode([
        'require-dev' => ['symfony/var-dumper' => '^7.0'],
    ]));

    $packages = (new ComposerLock($base))->scan();

    $dumper = $packages->first(fn ($p): bool => $p->name() === 'symfony/var-dumper');
    expect($dumper)->not->toBeNull()
        ->and($dumper->isDev())->toBeFalse()
        ->and($dumper->isDirect())->toBeTrue()
        ->and($dumper->constraint())->toEqual('^7.0');

    cleanup($base);
});
