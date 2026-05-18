<?php

namespace Laravel\Roster\Scanners;

use Laravel\Roster\PackageCollection;

/**
 * Fallback scanner when no JS lockfile is committed. Reads package.json
 * directly — versions reflect declared constraints, not resolved installs.
 */
class PackageJson extends BasePackageScanner
{
    protected function lockFile(): string
    {
        return 'package.json';
    }

    public function scan(): PackageCollection
    {
        $packages = new PackageCollection;

        // direct() reads package.json; every entry is direct by definition.
        $deps = [];
        foreach ($this->direct() as $name => $meta) {
            $deps[$name] = $meta['constraint'];
        }

        // processDependencies re-reads direct() for dev/constraint metadata.
        $this->processDependencies($deps, $packages, false);

        return $packages;
    }
}
