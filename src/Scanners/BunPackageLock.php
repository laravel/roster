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

        /** @var array<string, string> $nestedPackages */
        $nestedPackages = [];

        foreach ($json['packages'] as $key => $entry) {
            $key = (string) $key;
            $name = $this->extractName($entry) ?? $key;

            if ($name === $key) {
                $allPackages[$name] = $this->extractVersion($entry);
            } elseif (! isset($nestedPackages[$name])) {
                $nestedPackages[$name] = $this->extractVersion($entry);
            }
        }

        $this->processDependencies($allPackages + $nestedPackages, $packages, false);

        return $packages;
    }

    private function extractName(mixed $entry): ?string
    {
        if (! is_array($entry) || ! isset($entry[0]) || ! is_string($entry[0])) {
            return null;
        }

        $position = strrpos($entry[0], '@');

        return $position === false || $position === 0 ? null : substr($entry[0], 0, $position);
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
