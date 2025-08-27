<?php

namespace Laravel\Roster\Scanners;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class NpmPackageLock extends BasePackageScanner
{
    /**
     * @return \Illuminate\Support\Collection<int, \Laravel\Roster\Package|\Laravel\Roster\Approach>
     */
    public function scan(): Collection
    {
        $mappedItems = collect();
        $lockFilePath = $this->path.'package-lock.json';

        $contents = $this->validateFile($lockFilePath);
        if ($contents === null) {
            return $mappedItems;
        }

        $json = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($json)) {
            Log::warning('Failed to decode Package: '.$lockFilePath.'. '.json_last_error_msg());

            return $mappedItems;
        }

        if (! array_key_exists('packages', $json)) {
            Log::warning('Malformed package-lock');

            return $mappedItems;
        }

        $dependencies = $json['packages']['']['dependencies'] ?? [];
        $devDependencies = $json['packages']['']['devDependencies'] ?? [];
        $packages = array_filter($json['packages'], fn ($key) => $key !== '', ARRAY_FILTER_USE_KEY);

        $versionCb = function (string $packageName, string $version) use ($packages): string {
            $key = "node_modules/{$packageName}";
            if (array_key_exists($key, $packages)) {
                return $packages[$key]['version'];
            }

            return $version;
        };

        $this->processDependencies($dependencies, $mappedItems, false, $versionCb);
        $this->processDependencies($devDependencies, $mappedItems, true, $versionCb);

        return $mappedItems;
    }

    /**
     * Check if the scanner can handle the given path
     */
    public function canScan(): bool
    {
        return file_exists($this->path.'package-lock.json');
    }
}
