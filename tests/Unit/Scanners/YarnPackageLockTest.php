<?php

declare(strict_types=1);

use Laravel\Roster\Scanners\YarnPackageLock;

it('scans yarn v1 lockfiles', function (): void {
    $base = fixtureCopy([
        'fog/package.json' => 'package.json',
        'fog/yarn-v1.lock' => 'yarn.lock',
    ]);

    $packages = (new YarnPackageLock($base))->scan();

    $quickLru = $packages->first(fn ($p): bool => $p->name() === '@alloc/quick-lru');
    expect($quickLru)->not->toBeNull();
    expect($quickLru->version())->toEqual('5.2.0');

    $cliui = $packages->first(fn ($p): bool => $p->name() === '@isaacs/cliui');
    expect($cliui)->not->toBeNull();
    expect($cliui->version())->toEqual('8.0.2');

    cleanup($base);
});

it('scans yarn berry lockfiles', function (): void {
    $base = fixtureCopy([
        'fog/package.json' => 'package.json',
        'fog/yarn.lock' => 'yarn.lock',
    ]);

    $packages = (new YarnPackageLock($base))->scan();

    $parser = $packages->first(fn ($p): bool => $p->name() === '@babel/parser');
    expect($parser)->not->toBeNull();
    expect($parser->version())->toEqual('7.28.5');

    $stringParser = $packages->first(fn ($p): bool => $p->name() === '@babel/helper-string-parser');
    expect($stringParser)->not->toBeNull();
    expect($stringParser->version())->toEqual('7.27.1');

    cleanup($base);
});

it('parses quoted scoped v1 headers', function (): void {
    $base = fixtureCopy(['yarn-scoped-quoted/yarn.lock' => 'yarn.lock']);

    $packages = (new YarnPackageLock($base))->scan();

    $inertia = $packages->first(fn ($p): bool => $p->name() === '@inertiajs/react');
    expect($inertia)->not->toBeNull();
    expect($inertia->version())->toEqual('2.0.12');

    $tailwind = $packages->first(fn ($p): bool => $p->name() === 'tailwindcss');
    expect($tailwind)->not->toBeNull();
    expect($tailwind->version())->toEqual('3.4.16');

    cleanup($base);
});

it('parses unquoted scoped v1 headers', function (): void {
    $base = fixtureCopy(['yarn-scoped-unquoted/yarn.lock' => 'yarn.lock']);

    $packages = (new YarnPackageLock($base))->scan();

    $inertia = $packages->first(fn ($p): bool => $p->name() === '@inertiajs/vue3');
    expect($inertia)->not->toBeNull();
    expect($inertia->version())->toEqual('2.0.5');

    $alpine = $packages->first(fn ($p): bool => $p->name() === 'alpinejs');
    expect($alpine)->not->toBeNull();
    expect($alpine->version())->toEqual('3.4.4');

    cleanup($base);
});

it('returns an empty collection when the lockfile is missing', function (): void {
    $base = tempBase();

    expect((new YarnPackageLock($base))->scan())->toHaveCount(0);

    cleanup($base);
});
