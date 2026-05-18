<?php

use Laravel\Roster\Enums\JsPackageManager;
use Laravel\Roster\Registry;
use Laravel\Roster\Scanners\JsLockfile;

$fogDir = __DIR__.'/../../fixtures/fog/';
$packageLock = $fogDir.'package-lock.json';
$pnpmLock = $fogDir.'pnpm-lock.yaml';
$yarnLock = $fogDir.'yarn.lock';
$yarnV1 = $fogDir.'yarn-v1.lock';
$bunLock = $fogDir.'bun.lock';

afterEach(function () use ($packageLock, $pnpmLock, $yarnLock, $yarnV1, $bunLock) {
    foreach ([$packageLock, $pnpmLock, $yarnLock, $yarnV1, $bunLock] as $file) {
        if (file_exists($file.'.bac')) {
            rename($file.'.bac', $file);
        }
    }
});

it('scans package-lock.json when present', function () use ($fogDir) {
    $packages = (new JsLockfile($fogDir, new Registry))->scan();

    $tailwind = $packages->first(fn ($p) => $p->name() === 'tailwindcss');
    expect($tailwind->version())->toEqual('3.4.16');
    expect($tailwind->path())->toEndWith('node_modules'.DIRECTORY_SEPARATOR.'tailwindcss');

    $echoReact = $packages->first(fn ($p) => $p->name() === '@laravel/echo-react');
    expect($echoReact)->not->toBeNull();
});

it('falls back to pnpm-lock when package-lock missing', function () use ($fogDir, $packageLock) {
    rename($packageLock, $packageLock.'.bac');

    $packages = (new JsLockfile($fogDir, new Registry))->scan();
    $tailwind = $packages->first(fn ($p) => $p->name() === 'tailwindcss');
    expect($tailwind->version())->toEqual('3.4.3');
});

it('reports the committed manager from lockfile presence', function () use ($fogDir) {
    $manager = (new JsLockfile($fogDir, new Registry))->committedManager();
    expect($manager)->toBe(JsPackageManager::NPM);
});

it('falls back to package.json when no lockfile is committed', function () {
    $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'roster_pkgjson_'.uniqid();
    mkdir($tempDir);

    file_put_contents($tempDir.DIRECTORY_SEPARATOR.'package.json', json_encode([
        'dependencies' => ['vue' => '^3.4.0'],
        'devDependencies' => ['@inertiajs/react' => '^2.0.0'],
    ]));

    $packages = (new JsLockfile($tempDir.DIRECTORY_SEPARATOR, new Registry))->scan();

    $vue = $packages->first(fn ($p) => $p->name() === 'vue');
    expect($vue)->not->toBeNull();
    expect($vue->version())->toEqual('3.4.0');
    expect($vue->isDirect())->toBeTrue();

    $inertia = $packages->first(fn ($p) => $p->name() === '@inertiajs/react');
    expect($inertia)->not->toBeNull();
    expect($inertia->alias())->toBe('inertia-react');
    expect($inertia->isDev())->toBeTrue();

    unlink($tempDir.DIRECTORY_SEPARATOR.'package.json');
    rmdir($tempDir);
});

it('returns null committedManager when no lockfile present', function () {
    $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'roster_pkgjson_only_'.uniqid();
    mkdir($tempDir);

    $manager = (new JsLockfile($tempDir.DIRECTORY_SEPARATOR, new Registry))->committedManager();
    expect($manager)->toBeNull();

    rmdir($tempDir);
});
