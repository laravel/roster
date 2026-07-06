<?php

declare(strict_types=1);

namespace Laravel\Roster\Scanners;

use Laravel\Roster\PackageCollection;

class NpmPackageLock extends JsPackageScanner
{
    public function scan(): PackageCollection
    {
        $packages = new PackageCollection;
        $lockFilePath = $this->path.'package-lock.json';

        $json = self::readJsonFile($lockFilePath);

        if ($json === null) {
            if (file_exists($lockFilePath)) {
                $this->warn('Failed to decode package-lock.json: '.$lockFilePath);
            }

            $this->markFailed();

            return $packages;
        }

        if (! is_array($json['packages'] ?? null)) {
            $this->warn('Unsupported package-lock.json (missing "packages" key): '.$lockFilePath);

            $this->markFailed();

            return $packages;
        }

        /** @var array<string, array<string, mixed>> $jsonPackages */
        $jsonPackages = $json['packages'];

        /** @var array<string, string> $prodPackages */
        $prodPackages = [];

        /** @var array<string, string> $devPackages */
        $devPackages = [];

        foreach ($jsonPackages as $key => $entry) {
            if ($key === '') {
                continue;
            }

            $name = $this->nameFromNodeModulesPath($key);

            if ($name === null) {
                continue;
            }

            if (isset($prodPackages[$name])) {
                continue;
            }

            if (isset($devPackages[$name])) {
                continue;
            }

            $version = isset($entry['version']) && is_scalar($entry['version']) ? (string) $entry['version'] : '';

            if (($entry['dev'] ?? false) === true) {
                $devPackages[$name] = $version;
            } else {
                $prodPackages[$name] = $version;
            }
        }

        $this->processDependencies($prodPackages, $packages, false, authoritative: true);
        $this->processDependencies($devPackages, $packages, true, authoritative: true);

        return $packages;
    }

    private function nameFromNodeModulesPath(string $key): ?string
    {
        $marker = 'node_modules/';

        if (! str_starts_with($key, $marker) || substr_count($key, $marker) !== 1) {
            return null;
        }

        $name = substr($key, strlen($marker));

        return $name === '' ? null : $name;
    }
}
