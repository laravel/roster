<?php

declare(strict_types=1);

namespace Laravel\Roster\Scanners;

use Laravel\Roster\PackageCollection;

class BunPackageLock extends JsPackageScanner
{
    public function scan(): PackageCollection
    {
        $packages = new PackageCollection;
        $lockFilePath = $this->path.'bun.lock';

        $contents = $this->readContents($lockFilePath, 'bun.lock');

        if ($contents === null) {
            return $packages;
        }

        $sanitized = preg_replace('/,\s*([]}])/m', '$1', $contents) ?? $contents;

        $json = json_decode($sanitized, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($json)) {
            $this->warn('Failed to decode bun.lock: '.$lockFilePath);

            return $packages;
        }

        if (! is_array($json['packages'] ?? null)) {
            $this->warn('Malformed bun.lock (missing "packages" key): '.$lockFilePath);

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
