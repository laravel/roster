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

it('classifies bun root devDependencies as dev', function (): void {
    $base = tempBase();

    file_put_contents($base.'bun.lock', json_encode([
        'lockfileVersion' => 1,
        'workspaces' => [
            '' => [
                'name' => 'app',
                'dependencies' => ['tailwindcss' => '^3.4.3'],
                'devDependencies' => ['vite' => '^5.0.0'],
            ],
        ],
        'packages' => [
            'tailwindcss' => ['tailwindcss@3.4.3', '', [], 'sha512-a'],
            'vite' => ['vite@5.0.0', '', [], 'sha512-b'],
        ],
    ]));

    $packages = (new BunPackageLock($base))->scan();

    $vite = $packages->first(fn ($p): bool => $p->name() === 'vite');
    $tailwind = $packages->first(fn ($p): bool => $p->name() === 'tailwindcss');

    expect($vite)->not->toBeNull()
        ->and($vite->isDev())->toBeTrue()
        ->and($tailwind)->not->toBeNull()
        ->and($tailwind->isDev())->toBeFalse();

    cleanup($base);
});

it('strips structural trailing commas without corrupting string values', function (): void {
    $scanner = new class(sys_get_temp_dir().DIRECTORY_SEPARATOR) extends BunPackageLock
    {
        /** @return array<string, mixed>|null */
        public function decodePublic(string $contents): ?array
        {
            return $this->decodeLockfile($contents);
        }
    };

    $decoded = $scanner->decodePublic(<<<'JSON'
    {
      "lockfileVersion": 1,
      "note": "keep this ,} intact",
    }
    JSON);

    expect($decoded)->not->toBeNull()
        ->and($decoded['note'])->toBe('keep this ,} intact');
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

it('emits a root devDependency once with the direct version when it also appears as a nested transitive', function (): void {
    $base = tempBase();

    file_put_contents($base.'bun.lock', json_encode([
        'lockfileVersion' => 1,
        'workspaces' => [
            '' => [
                'dependencies' => ['@typescript-eslint/parser' => '^8.0.0'],
                'devDependencies' => ['eslint' => '^9.0.0'],
            ],
        ],
        'packages' => [
            'eslint' => ['eslint@9.2.0', '', [], 'sha512-a'],
            '@typescript-eslint/parser' => ['@typescript-eslint/parser@8.0.0', '', [], 'sha512-b'],
            '@typescript-eslint/parser/eslint' => ['eslint@8.1.0', '', [], 'sha512-c'],
        ],
    ]));
    file_put_contents($base.'package.json', json_encode([
        'dependencies' => ['@typescript-eslint/parser' => '^8.0.0'],
        'devDependencies' => ['eslint' => '^9.0.0'],
    ]));

    $packages = (new BunPackageLock($base))->scan();

    $eslint = $packages->filter(fn ($p): bool => $p->name() === 'eslint');
    expect($eslint)->toHaveCount(1)
        ->and($eslint->first()->version())->toEqual('9.2.0')
        ->and($eslint->first()->isDev())->toBeTrue()
        ->and($eslint->first()->isDirect())->toBeTrue();

    cleanup($base);
});
