<?php

declare(strict_types=1);

namespace Laravel\Roster\Scanners;

use Illuminate\Support\Facades\Log;
use Laravel\Roster\PackageCollection;

class BunPackageLock extends JsPackageScanner
{
    /**
     * Only the textual `bun.lock` format is parseable. Projects that ship the
     * legacy binary `bun.lockb` are still identified as Bun by JsLockfile,
     * but no packages can be extracted from the binary format.
     */
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

        if (! isset($json['packages']) || ! is_array($json['packages'])) {
            Log::warning('Malformed bun.lock');

            return $packages;
        }

        /** @var array<string, string> $allPackages */
        $allPackages = [];
        foreach ($json['packages'] as $name => $entry) {
            if (! is_string($name)) {
                continue;
            }

            if (isset($allPackages[$name])) {
                continue;
            }

            $allPackages[$name] = $this->extractVersion($entry);
        }

        $this->processDependencies($allPackages, $packages, false);

        return $packages;
    }

    private function extractVersion(mixed $entry): string
    {
        if (is_array($entry) && isset($entry[0]) && is_string($entry[0])) {
            $position = strrpos($entry[0], '@');

            return $position === false ? $entry[0] : substr($entry[0], $position + 1);
        }

        if (is_string($entry)) {
            return $entry;
        }

        return '';
    }
}
