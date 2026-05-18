<?php

namespace Laravel\Roster\Scanners;

use Illuminate\Support\Facades\Log;
use Laravel\Roster\PackageCollection;

class NpmPackageLock extends BasePackageScanner
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
        $root = $jsonPackages[''] ?? [];
        /** @var array<string, string> $dependencies */
        $dependencies = $root['dependencies'] ?? [];
        /** @var array<string, string> $devDependencies */
        $devDependencies = $root['devDependencies'] ?? [];

        $versionCb = function (string $packageName, string $version) use ($jsonPackages): string {
            $entry = $jsonPackages["node_modules/{$packageName}"] ?? null;
            if (is_array($entry) && isset($entry['version']) && is_scalar($entry['version'])) {
                return (string) $entry['version'];
            }

            return $version;
        };

        $this->processDependencies($dependencies, $packages, false, $versionCb);
        $this->processDependencies($devDependencies, $packages, true, $versionCb);

        return $packages;
    }
}
