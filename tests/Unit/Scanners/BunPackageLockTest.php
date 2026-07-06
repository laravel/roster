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

it('resolves nested keys to the real package name and prefers the top-level entry', function (): void {
    $base = tempBase();

    file_put_contents($base.'bun.lock', json_encode([
        'lockfileVersion' => 1,
        'packages' => [
            'jest/pretty-format' => ['pretty-format@29.7.0', '', [], 'sha512-b'],
            'pretty-format' => ['pretty-format@30.0.0', '', [], 'sha512-a'],
        ],
    ]));

    $packages = (new BunPackageLock($base))->scan();

    expect($packages)->toHaveCount(1);

    $prettyFormat = $packages->first(fn ($p): bool => $p->name() === 'pretty-format');
    expect($prettyFormat)->not->toBeNull()
        ->and($prettyFormat->version())->toEqual('30.0.0');

    cleanup($base);
});

it('keeps npm-aliased top-level entries under their alias name', function (): void {
    $base = tempBase();

    file_put_contents($base.'bun.lock', json_encode([
        'lockfileVersion' => 1,
        'packages' => [
            'my-lodash' => ['lodash@4.17.21', '', [], 'sha512-a'],
        ],
    ]));
    file_put_contents($base.'package.json', json_encode([
        'dependencies' => ['my-lodash' => 'npm:lodash@^4.17.0'],
    ]));

    $packages = (new BunPackageLock($base))->scan();

    $aliased = $packages->first(fn ($p): bool => $p->name() === 'my-lodash');
    expect($aliased)->not->toBeNull()
        ->and($aliased->version())->toEqual('4.17.21')
        ->and($aliased->isDirect())->toBeTrue()
        ->and($packages->first(fn ($p): bool => $p->name() === 'lodash'))->toBeNull();

    cleanup($base);
});
