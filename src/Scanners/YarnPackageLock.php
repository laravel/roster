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

            // Package header line (e.g. tailwindcss@^3.4.3:)
            if (preg_match('/^("?)([^@"]+)(@[^:]+)?:\1$/', $line, $matches)) {
                $currentPackage = $matches[2];
            }
            // Version line
            elseif ($currentPackage && preg_match('/^version\s+"?([^"]+)"?$/', $line, $matches)) {
                $version = $matches[1];
                $dependencies[$currentPackage] = $version;
                $currentPackage = null; // Reset until next package block
            }
        }

        // Yarn lock does not distinguish devDependencies :/
        $this->processDependencies($dependencies, $mappedItems, false);

        return $mappedItems;
    }

    /**
     * Check if the scanner can handle the given path
     */
    public function canScan(): bool
    {
        return file_exists($this->path.'yarn.lock');
    }
}
