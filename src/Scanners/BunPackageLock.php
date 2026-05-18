<?php

namespace Laravel\Roster\Scanners;

use Illuminate\Support\Facades\Log;
use Laravel\Roster\PackageCollection;

class BunPackageLock extends BasePackageScanner
{
    protected function lockFile(): string
    {
        return 'bun.lock';
    }

    public function scan(): PackageCollection
    {
        $packages = new PackageCollection;
        $lockFilePath = $this->lockFilePath();

        $contents = $this->readContents($lockFilePath);
        if ($contents === null) {
            return $packages;
        }

        // Bun's lock format is JSON-like but permits trailing commas.
        $sanitized = preg_replace('/,\s*([]}])/m', '$1', $contents) ?? $contents;

        $json = json_decode($sanitized, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($json)) {
            Log::warning('Failed to decode bun.lock: '.$lockFilePath);

            return $packages;
        }

        if (! isset($json['workspaces']) || ! is_array($json['workspaces'])
            || ! isset($json['workspaces'][''], $json['packages'])) {
            Log::warning('Malformed bun.lock');

            return $packages;
        }

        /** @var array<string, mixed> $workspace */
        $workspace = $json['workspaces'][''];

        /** @var array<string, string> $dependencies */
        $dependencies = $workspace['dependencies'] ?? [];
        /** @var array<string, string> $devDependencies */
        $devDependencies = $workspace['devDependencies'] ?? [];

        $this->processDependencies($dependencies, $packages, false);
        $this->processDependencies($devDependencies, $packages, true);

        return $packages;
    }
}
