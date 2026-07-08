<?php

declare(strict_types=1);

use Laravel\Roster\Ecosystems\Ecosystem;
use Laravel\Roster\Ecosystems\JsEcosystem;
use Laravel\Roster\Enums\PackageSource;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;

/**
 * @param  array<int, string|array{name: string, version?: string, dev?: bool, direct?: bool}>  $specs
 */
function phpEcosystem(array $specs): Ecosystem
{
    return new Ecosystem(packagesFromSpecs($specs, PackageSource::Composer));
}

/**
 * @param  array<int, string|array{name: string, version?: string, dev?: bool, direct?: bool}>  $specs
 */
function jsEcosystem(array $specs): JsEcosystem
{
    return new JsEcosystem(
        packagesFromSpecs($specs, PackageSource::Npm),
        null,
    );
}

/**
 * @return list<string>
 */
function trackedTempDirs(?string $add = null, bool $reset = false): array
{
    static $dirs = [];

    if ($reset) {
        $dirs = [];
    } elseif ($add !== null) {
        $dirs[] = $add;
    }

    return $dirs;
}

function tempBase(): string
{
    $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'roster_test_'.uniqid().DIRECTORY_SEPARATOR;
    mkdir($base);
    trackedTempDirs($base);

    return $base;
}

afterEach(function (): void {
    foreach (trackedTempDirs() as $dir) {
        cleanup($dir);
    }

    trackedTempDirs(reset: true);
});

/**
 * @param  array<string, string>  $files
 */
function fixtureCopy(array $files): string
{
    $base = tempBase();

    foreach ($files as $source => $destination) {
        copy(__DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $source), $base.$destination);
    }

    return $base;
}

function touchFile(string $path): void
{
    $dir = dirname($path);

    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    file_put_contents($path, '');
}

function cleanup(string $base): void
{
    if (! is_dir($base)) {
        return;
    }

    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iter as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }

    rmdir($base);
}

/**
 * @param  array<int, string|array{name: string, version?: string, dev?: bool, direct?: bool}>  $specs
 */
function packagesFromSpecs(array $specs, PackageSource $source): PackageCollection
{
    $packages = new PackageCollection;

    foreach ($specs as $spec) {
        if (is_string($spec)) {
            $spec = ['name' => $spec];
        }

        $packages->push(new Package(
            name: $spec['name'],
            version: $spec['version'] ?? '1.0.0',
            source: $source,
            dev: $spec['dev'] ?? false,
            direct: $spec['direct'] ?? true,
        ));
    }

    return $packages;
}
