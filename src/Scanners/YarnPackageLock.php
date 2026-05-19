<?php

namespace Laravel\Roster\Scanners;

use Laravel\Roster\PackageCollection;

class YarnPackageLock extends BasePackageScanner
{
    private const YARN_V1_HEADER = '/^("?)(@[^@"\/]+\/[^@"]+|[^@"]+)(@[^:"]+)?\1:$/';

    private const YARN_V4_HEADER = '/^"(@?[^@"]+(?:\/[^@"]+)?)@npm:[^"]*":\s*$/';

    private const YARN_V1_VERSION = '/^version\s+"([^"]+)"$/';

    private const YARN_V4_VERSION = '/^version:\s+(.+)$/';

    protected function lockFile(): string
    {
        return 'yarn.lock';
    }

    public function scan(): PackageCollection
    {
        $packages = new PackageCollection;
        $lockFilePath = $this->lockFilePath();

        $contents = $this->readContents($lockFilePath, 'Yarn lock');
        if ($contents === null) {
            return $packages;
        }

        $dependencies = [];
        $lines = explode("\n", $contents);
        $currentPackage = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (str_starts_with($line, '#')) {
                continue;
            }

            $packageName = $this->parsePackageHeader($line);
            if ($packageName !== null) {
                $currentPackage = $packageName;

                continue;
            }

            $version = $this->parseVersion($line);
            if ($currentPackage !== null && $version !== null) {
                $dependencies[$currentPackage] = $version;
                $currentPackage = null;
            }
        }

        // Yarn lock does not distinguish devDependencies; package.json fills that in.
        $this->processDependencies($dependencies, $packages, false);

        return $packages;
    }

    private function parsePackageHeader(string $line): ?string
    {
        if (preg_match(self::YARN_V1_HEADER, $line, $matches)) {
            return $matches[2];
        }

        if (preg_match(self::YARN_V4_HEADER, $line, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function parseVersion(string $line): ?string
    {
        if (preg_match(self::YARN_V1_VERSION, $line, $matches)) {
            return $matches[1];
        }

        if (preg_match(self::YARN_V4_VERSION, $line, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
