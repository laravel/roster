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

        $contents = $this->validateFile($lockFilePath);
        if ($contents === null) {
            return $packages;
        }

        $json = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($json) || ! array_key_exists('packages', $json)) {
            Log::warning('Failed to decode package-lock: '.$lockFilePath);

            return $packages;
        }

        /** @var array<string, array<string, mixed>> $jsonPackages */
        $jsonPackages = $json['packages'];
        $root = $jsonPackages[''] ?? [];
        /** @var array<string, string> $dependencies */
        $dependencies = $root['dependencies'] ?? [];
        /** @var array<string, string> $devDependencies */
        $devDependencies = $root['devDependencies'] ?? [];
        $allPackages = array_filter($jsonPackages, fn ($key) => $key !== '', ARRAY_FILTER_USE_KEY);

        $versionCb = function (string $packageName, string $version) use ($allPackages): string {
            $key = "node_modules/{$packageName}";
            if (array_key_exists($key, $allPackages) && isset($allPackages[$key]['version']) && is_scalar($allPackages[$key]['version'])) {
                return (string) $allPackages[$key]['version'];
            }

            return $version;
        };

        $this->processDependencies($dependencies, $packages, false, $versionCb);
        $this->processDependencies($devDependencies, $packages, true, $versionCb);

        return $packages;
    }
}
