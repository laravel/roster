<?php

use Laravel\Roster\Detectors\PackageManagersDetection;
use Laravel\Roster\Ecosystems\JsEcosystem;
use Laravel\Roster\Ecosystems\PhpEcosystem;
use Laravel\Roster\Enums\PackageSource;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;

expect()->extend('toBeOne', fn() => $this->toBe(1));

/**
 * @param  array<int, string|array{name: string, version?: string, dev?: bool, direct?: bool, alias?: string|null}>  $specs
 */
function phpEcosystem(array $specs): PhpEcosystem
{
    return new PhpEcosystem(packagesFromSpecs($specs, PackageSource::COMPOSER));
}

/**
 * @param  array<int, string|array{name: string, version?: string, dev?: bool, direct?: bool, alias?: string|null}>  $specs
 */
function jsEcosystem(array $specs): JsEcosystem
{
    return new JsEcosystem(
        packagesFromSpecs($specs, PackageSource::NPM),
        new PackageManagersDetection([], []),
    );
}

function tempBase(): string
{
    $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'roster_test_'.uniqid().DIRECTORY_SEPARATOR;
    mkdir($base);

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
 * @param  array<int, string|array{name: string, version?: string, dev?: bool, direct?: bool, alias?: string|null}>  $specs
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
            alias: $spec['alias'] ?? null,
            dev: $spec['dev'] ?? false,
            direct: $spec['direct'] ?? false,
        ));
    }

    return $packages;
}
