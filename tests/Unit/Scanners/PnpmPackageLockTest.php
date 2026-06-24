<?php

declare(strict_types=1);

use Laravel\Roster\Scanners\PnpmPackageLock;

function writePnpmProject(string $lock, string $packageJson): string
{
    $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'roster_pnpm_'.uniqid();
    mkdir($dir);
    file_put_contents($dir.DIRECTORY_SEPARATOR.'pnpm-lock.yaml', $lock);
    file_put_contents($dir.DIRECTORY_SEPARATOR.'package.json', $packageJson);

    return $dir.DIRECTORY_SEPARATOR;
}

it('parses v9 keys with peer-dependency suffixes', function (): void {
    $lock = <<<'YAML'
    lockfileVersion: '9.0'

    importers:

      .:
        dependencies:
          vue:
            specifier: ^3.4.0
            version: 3.4.0(typescript@5.3.0)

    packages:

      vue@3.4.0:
        resolution: {integrity: sha512-abc==}

      react-dom@18.2.0(react@18.2.0):
        resolution: {integrity: sha512-def==}

      '@babel/core@7.0.0':
        resolution: {integrity: sha512-ghi==}
    YAML;

    $base = writePnpmProject($lock, json_encode(['dependencies' => ['vue' => '^3.4.0']]));

    $packages = (new PnpmPackageLock($base))->scan();

    $vue = $packages->first(fn ($p): bool => $p->name() === 'vue');
    $reactDom = $packages->first(fn ($p): bool => $p->name() === 'react-dom');
    $babel = $packages->first(fn ($p): bool => $p->name() === '@babel/core');

    expect($vue)->not->toBeNull()
        ->and($vue->version())->toEqual('3.4.0')
        ->and($vue->isDirect())->toBeTrue()
        ->and($reactDom)->not->toBeNull()
        ->and($reactDom->version())->toEqual('18.2.0')
        ->and($babel)->not->toBeNull()
        ->and($babel->version())->toEqual('7.0.0');
});

it('parses v6 slash-delimited keys', function (): void {
    $lock = <<<'YAML'
    lockfileVersion: 6.0

    packages:

      /lodash/4.17.21:
        resolution: {integrity: sha512-abc==}

      /@babel/core/7.0.0:
        resolution: {integrity: sha512-def==}
    YAML;

    $base = writePnpmProject($lock, json_encode(['dependencies' => []]));

    $packages = (new PnpmPackageLock($base))->scan();

    $lodash = $packages->first(fn ($p): bool => $p->name() === 'lodash');
    $babel = $packages->first(fn ($p): bool => $p->name() === '@babel/core');

    expect($lodash)->not->toBeNull()
        ->and($lodash->version())->toEqual('4.17.21')
        ->and($babel)->not->toBeNull()
        ->and($babel->version())->toEqual('7.0.0');
});
