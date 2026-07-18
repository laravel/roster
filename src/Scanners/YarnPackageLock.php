<?php

declare(strict_types=1);

namespace Laravel\Roster\Scanners;

use Laravel\Roster\PackageCollection;

class YarnPackageLock extends JsPackageScanner
{
    private const YARN_V4_HEADER = '/^"(@?[^@"]+(?:\/[^@"]+)?)@npm:[^"]*":\s*$/';

    private const YARN_V1_VERSION = '/^version\s+"([^"]+)"$/';

    private const YARN_V4_VERSION = '/^version:\s+(.+)$/';

    public function scan(): PackageCollection
    {
        $packages = new PackageCollection;
        $lockFilePath = $this->path.'yarn.lock';

        $contents = $this->readContents($lockFilePath, 'yarn.lock');

        if ($contents === null) {
            $this->failed = true;

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

        if ($dependencies === []) {
            $this->failed = true;

            return $packages;
        }

        $this->processDependencies($dependencies, $packages, false);

        return $packages;
    }

    private function parsePackageHeader(string $line): ?string
    {
        if (preg_match(self::YARN_V4_HEADER, $line, $matches)) {
            return $matches[1];
        }

        if (! str_ends_with($line, ':')) {
            return null;
        }

        // yarn v1: `lodash@^4.0.0:`, `"@babel/core@^7.0.0", "@babel/core@^7.2.0":`.
        $selector = trim((string) strtok(substr($line, 0, -1), ','), " \t\"");

        // Split at the first `@` past the leading scope marker: package names
        // cannot contain `@`, while ranges may (patch:, git+ssh:// selectors).
        $position = strpos($selector, '@', 1);

        if ($position === false || $position === 0) {
            return null;
        }

        // Skip workspace selectors (`my-app@workspace:.`, `pkg-a@workspace:pkgs/a`):
        if (str_starts_with(substr($selector, $position + 1), 'workspace:')) {
            return null;
        }

        return substr($selector, 0, $position);
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
