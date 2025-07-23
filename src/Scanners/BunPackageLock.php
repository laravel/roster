<?php

namespace Laravel\Roster\Scanners;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BunPackageLock extends BasePackageScanner
{
    /**
     * @return \Illuminate\Support\Collection<int, \Laravel\Roster\Package|\Laravel\Roster\Approach>
     */
    public function scan(): Collection
    {
        $mappedItems = collect();
        $lockFilePath = $this->path.'bun.lock';

        $contents = $this->validateFile($lockFilePath);
        if ($contents === null) {
            return $mappedItems;
        }

        // Remove trailing commas before decoding
        /** @var string $contents */
        $contents = preg_replace('/,\s*([]}])/m', '$1', $contents);
        $json = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($json)) {
            Log::warning('Failed to decode Package: '.$lockFilePath.'. '.json_last_error_msg());

            return $mappedItems;
        }

        /** @var array<string, array<string, mixed>> $json */
        if (! isset($json['workspaces']['']) || ! isset($json['packages'])) {
            Log::warning('Malformed bun.lock');

            return $mappedItems;
        }

        /** @var array<string, mixed> $workspace */
        $workspace = $json['workspaces'][''];

        /** @var array<string, string> $dependencies */
        $dependencies = $workspace['dependencies'] ?? [];
        /** @var array<string, string> $devDependencies */
        $devDependencies = $workspace['devDependencies'] ?? [];

        $this->processDependencies($dependencies, $mappedItems, false);
        $this->processDependencies($devDependencies, $mappedItems, true);

        return $mappedItems;
    }

    /**
     * Check if the scanner can handle the given path
     */
    public function canScan(): bool
    {
        return file_exists($this->path.'bun.lock');
    }
}
