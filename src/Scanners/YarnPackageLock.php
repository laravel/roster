<?php

namespace Laravel\Roster\Scanners;

use Illuminate\Support\Collection;

class YarnPackageLock extends BasePackageScanner
{
    /**
     * @return \Illuminate\Support\Collection<int, \Laravel\Roster\Package|\Laravel\Roster\Approach>
     */
    public function scan(): Collection
    {
        $mappedItems = collect([]);
        $lockFilePath = $this->path.'yarn.lock';

        $contents = $this->validateFile($lockFilePath, 'Yarn lock');
        if ($contents === null) {
            return $mappedItems;
        }

        $dependencies = [];
        $lines = explode("\n", $contents);
        $currentPackage = null;

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if ($packageName = $this->parsePackageHeader($line)) {
                $currentPackage = $packageName;
            }
            elseif ($currentPackage && $version = $this->parseVersion($line)) {
                $dependencies[$currentPackage] = $version;
                $currentPackage = null; // Reset until next package block
            }
        }

        // Yarn lock does not distinguish devDependencies :/
        $this->processDependencies($dependencies, $mappedItems, false);

        return $mappedItems;
    }

    private function parsePackageHeader(string $line): ?string
    {
        // Yarn v1 format: tailwindcss@^3.4.3: or "tailwindcss@^3.4.3":
        if (preg_match('/^("?)([^@"]+)(@[^:]+)?:\1$/', $line, $matches)) {
            return $matches[2];
        }

        // Yarn v4 format: "tailwindcss@npm:4.1.16, tailwindcss@npm:^4.1.1": or "@inertiajs/vue3@npm:^2.0.0":
        if (preg_match('/^"(@?[^@"]+(?:\/[^@"]+)?)@npm:[^"]*":\s*$/', $line, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function parseVersion(string $line): ?string
    {
        // Yarn v1 format: version "3.4.16"
        if (preg_match('/^version\s+"([^"]+)"$/', $line, $matches)) {
            return $matches[1];
        }

        // Yarn v4 format: version: 4.1.16
        if (preg_match('/^version:\s+(.+)$/', $line, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Check if the scanner can handle the given path
     */
    public function canScan(): bool
    {
        return file_exists($this->path.'yarn.lock');
    }
}
