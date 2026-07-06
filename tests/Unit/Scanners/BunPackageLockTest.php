<?php

declare(strict_types=1);

use Laravel\Roster\Scanners\BunPackageLock;

it('scans bun.lock including JSONC trailing commas', function (): void {
    $base = fixtureCopy([
        'fog/package.json' => 'package.json',
        'fog/bun.lock' => 'bun.lock',
    ]);

    $packages = (new BunPackageLock($base))->scan();

    $alpine = $packages->first(fn ($p): bool => $p->name() === 'alpinejs');
    expect($alpine)->not->toBeNull();
    expect($alpine->version())->toEqual('3.14.8');

    $echoReact = $packages->first(fn ($p): bool => $p->name() === '@laravel/echo-react');
    expect($echoReact)->not->toBeNull();
    expect($echoReact->version())->toEqual('0.1.0');

    cleanup($base);
});

it('returns an empty collection for a malformed bun.lock', function (): void {
    $base = tempBase();
    file_put_contents($base.'bun.lock', '{not json');

    expect((new BunPackageLock($base))->scan())->toHaveCount(0);

    cleanup($base);
});

it('returns an empty collection when the packages key is missing', function (): void {
    $base = tempBase();
    file_put_contents($base.'bun.lock', json_encode(['lockfileVersion' => 1]));

    expect((new BunPackageLock($base))->scan())->toHaveCount(0);

    cleanup($base);
});
