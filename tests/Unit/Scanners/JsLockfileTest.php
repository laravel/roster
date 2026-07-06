<?php

declare(strict_types=1);

use Laravel\Roster\Enums\JsPackageManager;
use Laravel\Roster\Scanners\JsLockfile;

it('scans package-lock.json when present', function (): void {
    $base = fixtureCopy([
        'fog/package.json' => 'package.json',
        'fog/package-lock.json' => 'package-lock.json',
    ]);

    $packages = (new JsLockfile($base))->scan();

    $tailwind = $packages->first(fn ($p): bool => $p->name() === 'tailwindcss');
    expect($tailwind->version())->toEqual('3.4.16');
    expect($tailwind->path())->toEndWith('node_modules'.DIRECTORY_SEPARATOR.'tailwindcss');

    $echoReact = $packages->first(fn ($p): bool => $p->name() === '@laravel/echo-react');
    expect($echoReact)->not->toBeNull();

    cleanup($base);
});

it('marks transitive dev-only npm packages as dev', function (): void {
    $base = fixtureCopy([
        'fog/package.json' => 'package.json',
        'fog/package-lock.json' => 'package-lock.json',
    ]);

    $packages = (new JsLockfile($base))->scan();

    $quickLru = $packages->first(fn ($p): bool => $p->name() === '@alloc/quick-lru');
    expect($quickLru->isDev())->toBeTrue();
    expect($quickLru->isDirect())->toBeFalse();

    $codeFrame = $packages->first(fn ($p): bool => $p->name() === '@babel/code-frame');
    expect($codeFrame->isDev())->toBeFalse();
    expect($codeFrame->isDirect())->toBeFalse();

    cleanup($base);
});

it('prefers package-lock.json when multiple lockfiles are committed', function (): void {
    $base = fixtureCopy([
        'fog/package.json' => 'package.json',
        'fog/package-lock.json' => 'package-lock.json',
        'fog/pnpm-lock.yaml' => 'pnpm-lock.yaml',
    ]);

    $lockfile = new JsLockfile($base);

    expect($lockfile->committedManager())->toBe(JsPackageManager::NPM);

    $tailwind = $lockfile->scan()->first(fn ($p): bool => $p->name() === 'tailwindcss');
    expect($tailwind->version())->toEqual('3.4.16');

    cleanup($base);
});

it('scans pnpm-lock.yaml when it is the committed lockfile', function (): void {
    $base = fixtureCopy([
        'fog/package.json' => 'package.json',
        'fog/pnpm-lock.yaml' => 'pnpm-lock.yaml',
    ]);

    $lockfile = new JsLockfile($base);

    expect($lockfile->committedManager())->toBe(JsPackageManager::PNPM);

    $tailwind = $lockfile->scan()->first(fn ($p): bool => $p->name() === 'tailwindcss');
    expect($tailwind->version())->toEqual('3.4.3');

    cleanup($base);
});

it('scans yarn.lock when it is the committed lockfile', function (): void {
    $base = fixtureCopy([
        'fog/package.json' => 'package.json',
        'fog/yarn.lock' => 'yarn.lock',
    ]);

    $lockfile = new JsLockfile($base);

    expect($lockfile->committedManager())->toBe(JsPackageManager::YARN);

    $parser = $lockfile->scan()->first(fn ($p): bool => $p->name() === '@babel/parser');
    expect($parser->version())->toEqual('7.28.5');

    cleanup($base);
});

it('scans bun.lock when it is the committed lockfile', function (): void {
    $base = fixtureCopy([
        'fog/package.json' => 'package.json',
        'fog/bun.lock' => 'bun.lock',
    ]);

    $lockfile = new JsLockfile($base);

    expect($lockfile->committedManager())->toBe(JsPackageManager::BUN);

    $alpine = $lockfile->scan()->first(fn ($p): bool => $p->name() === 'alpinejs');
    expect($alpine->version())->toEqual('3.14.8');

    cleanup($base);
});

it('falls back to package.json when only bun.lockb is committed', function (): void {
    $tempDir = tempBase();
    file_put_contents($tempDir.'bun.lockb', "\x00binary");
    file_put_contents($tempDir.'package.json', json_encode([
        'dependencies' => ['vue' => '^3.4.0'],
    ]));

    $lockfile = new JsLockfile($tempDir);

    expect($lockfile->committedManager())->toBe(JsPackageManager::BUN);

    $vue = $lockfile->scan()->first(fn ($p): bool => $p->name() === 'vue');
    expect($vue)->not->toBeNull();
    expect($vue->version())->toEqual('3.4.0');

    cleanup($tempDir);
});

it('reports the committed manager from lockfile presence', function (): void {
    $manager = (new JsLockfile(__DIR__.'/../../fixtures/fog/'))->committedManager();
    expect($manager)->toBe(JsPackageManager::NPM);
});

it('falls back to package.json when no lockfile is committed', function (): void {
    $tempDir = tempBase();

    file_put_contents($tempDir.'package.json', json_encode([
        'dependencies' => ['vue' => '^3.4.0'],
        'devDependencies' => ['@inertiajs/react' => '^2.0.0'],
    ]));

    $packages = (new JsLockfile($tempDir))->scan();

    $vue = $packages->first(fn ($p): bool => $p->name() === 'vue');
    expect($vue)->not->toBeNull();
    expect($vue->version())->toEqual('3.4.0');
    expect($vue->isDirect())->toBeTrue();

    $inertia = $packages->first(fn ($p): bool => $p->name() === '@inertiajs/react');
    expect($inertia)->not->toBeNull();
    expect($inertia->isDev())->toBeTrue();

    cleanup($tempDir);
});

it('returns null committedManager when no lockfile present', function (): void {
    $tempDir = tempBase();

    $manager = (new JsLockfile($tempDir))->committedManager();
    expect($manager)->toBeNull();

    cleanup($tempDir);
});

it('falls back to package.json when the committed lockfile is unsupported', function (): void {
    $base = tempBase();

    file_put_contents($base.'package-lock.json', json_encode([
        'lockfileVersion' => 1,
        'dependencies' => ['vue' => ['version' => '3.4.0']],
    ]));
    file_put_contents($base.'package.json', json_encode(['dependencies' => ['vue' => '^3.4.0']]));

    $packages = (new JsLockfile($base))->scan();

    $vue = $packages->first(fn ($p): bool => $p->name() === 'vue');
    expect($vue)->not->toBeNull()
        ->and($vue->isDirect())->toBeTrue();

    cleanup($base);
});

it('does not substitute manifest data when the lockfile is valid but empty', function (): void {
    $base = tempBase();

    file_put_contents($base.'package-lock.json', json_encode([
        'lockfileVersion' => 3,
        'packages' => [],
    ]));
    file_put_contents($base.'package.json', json_encode(['dependencies' => ['vue' => '^3.4.0']]));

    expect((new JsLockfile($base))->scan())->toHaveCount(0);

    cleanup($base);
});
