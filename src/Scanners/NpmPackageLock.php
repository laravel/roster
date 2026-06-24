<?php

declare(strict_types=1);

namespace Laravel\Roster\Scanners;

use Illuminate\Support\Facades\Log;
use Laravel\Roster\PackageCollection;

class NpmPackageLock extends JsPackageScanner
{
    protected function lockFile(): string
    {
        return 'package-lock.json';
    }

    public function scan(): PackageCollection
    {
        $packages = new PackageCollection;
        $lockFilePath = $this->lockFilePath();

        $json = self::readJsonFile($lockFilePath);
        if ($json === null || ! array_key_exists('packages', $json)) {
            if (file_exists($lockFilePath)) {
                Log::warning('Failed to decode package-lock: '.$lockFilePath);
            }

            return $packages;
        }

        /** @var array<string, array<string, mixed>> $jsonPackages */
        $jsonPackages = $json['packages'];

        /** @var array<string, string> $allPackages */
        $allPackages = [];
        foreach ($jsonPackages as $key => $entry) {
            if ($key === '') {
                continue;
            }

            $name = $this->nameFromNodeModulesPath($key);
            if ($name === null) {
                continue;
            }

            if (isset($allPackages[$name])) {
                continue;
            }

            $version = isset($entry['version']) && is_scalar($entry['version']) ? (string) $entry['version'] : '';
            $allPackages[$name] = $version;
        }

        $this->processDependencies($allPackages, $packages, false);

        return $packages;
    }

    private function nameFromNodeModulesPath(string $key): ?string
    {
        $marker = 'node_modules/';

        // Only top-level entries (e.g. "node_modules/foo") are considered; nested
        // entries like "node_modules/foo/node_modules/bar" are skipped so that
        // each package is recorded once with its resolved top-level version.
        if (! str_starts_with($key, $marker) || substr_count($key, $marker) !== 1) {
            return null;
        }

        $name = substr($key, strlen($marker));

        return $name === '' ? null : $name;
    }
}
