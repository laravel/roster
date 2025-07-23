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

        $this->processDependencies($dependencies, $mappedItems, false);
        $this->processDependencies($devDependencies, $mappedItems, true);

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
