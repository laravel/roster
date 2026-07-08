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

it('parses v6 at-delimited keys and top-level root dependencies', function (): void {
    $lock = <<<'YAML'
    lockfileVersion: '6.0'

    dependencies:
      lodash:
        specifier: ^4.17.21
        version: 4.17.21

    packages:

      /lodash@4.17.21:
        resolution: {integrity: sha512-abc==}

      /@babel/core@7.0.0:
        resolution: {integrity: sha512-def==}

      /react-dom@18.2.0(react@18.2.0):
        resolution: {integrity: sha512-ghi==}
    YAML;

    $base = writePnpmProject($lock, json_encode(['dependencies' => ['lodash' => '^4.17.21']]));

    $packages = (new PnpmPackageLock($base))->scan();

    $lodash = $packages->first(fn ($p): bool => $p->name() === 'lodash');
    $babel = $packages->first(fn ($p): bool => $p->name() === '@babel/core');
    $reactDom = $packages->first(fn ($p): bool => $p->name() === 'react-dom');

    expect($lodash)->not->toBeNull()
        ->and($lodash->version())->toEqual('4.17.21')
        ->and($lodash->isDirect())->toBeTrue()
        ->and($babel)->not->toBeNull()
        ->and($babel->version())->toEqual('7.0.0')
        ->and($reactDom)->not->toBeNull()
        ->and($reactDom->version())->toEqual('18.2.0');
});

it('classifies root devDependencies as dev even without a package.json', function (): void {
    $lock = <<<'YAML'
    lockfileVersion: '9.0'

    importers:

      .:
        dependencies:
          vue:
            specifier: ^3.4.0
            version: 3.4.0
        devDependencies:
          eslint:
            specifier: ^9.0.0
            version: 9.0.0

    packages:

      vue@3.4.0:
        resolution: {integrity: sha512-abc==}

      eslint@9.0.0:
        resolution: {integrity: sha512-def==}
    YAML;

    $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'roster_pnpm_'.uniqid();
    mkdir($dir);
    file_put_contents($dir.DIRECTORY_SEPARATOR.'pnpm-lock.yaml', $lock);

    $packages = (new PnpmPackageLock($dir.DIRECTORY_SEPARATOR))->scan();

    $eslint = $packages->first(fn ($p): bool => $p->name() === 'eslint');
    $vue = $packages->first(fn ($p): bool => $p->name() === 'vue');

    expect($eslint)->not->toBeNull()
        ->and($eslint->isDev())->toBeTrue()
        ->and($vue)->not->toBeNull()
        ->and($vue->isDev())->toBeFalse();
});

it('flags an empty pnpm-lock.yaml as failed', function (): void {
    $base = writePnpmProject('', json_encode(['dependencies' => ['vue' => '^3.4.0']]));

    $scanner = new PnpmPackageLock($base);

    expect($scanner->scan())->toHaveCount(0)
        ->and($scanner->failed())->toBeTrue();
});

it('parses v5 keys with underscore peer suffixes', function (): void {
    $lock = <<<'YAML'
    lockfileVersion: 5.4

    dependencies:
      react-dom: 18.2.0_react@18.2.0

    packages:

      /react-dom/18.2.0_react@18.2.0:
        resolution: {integrity: sha512-abc==}

      /@babel/core/7.0.0:
        resolution: {integrity: sha512-def==}
    YAML;

    $base = writePnpmProject($lock, json_encode(['dependencies' => ['react-dom' => '^18.0.0']]));

    $packages = (new PnpmPackageLock($base))->scan();

    $reactDom = $packages->first(fn ($p): bool => $p->name() === 'react-dom');
    $babel = $packages->first(fn ($p): bool => $p->name() === '@babel/core');

    expect($reactDom)->not->toBeNull()
        ->and($reactDom->version())->toEqual('18.2.0')
        ->and($babel)->not->toBeNull()
        ->and($babel->version())->toEqual('7.0.0');
});
