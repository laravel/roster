<?php

namespace Laravel\Roster\Scanners;

use Illuminate\Support\Collection;

class PackageLock
{
    /**
     * @param  string  $path  - Base path to scan for lock files (package-lock.json, pnpm-lock.yaml, yarn.lock, ...)
     */
    public function __construct(protected string $path) {}

    /**
     * @return \Illuminate\Support\Collection<int, \Laravel\Roster\Package|\Laravel\Roster\Approach>
     */
    public function scan(): Collection
    {
        // Priority order: npm -> pnpm -> yarn -> bun
        $scanners = [
            new NpmPackageLock($this->path),
            new PnpmPackageLock($this->path),
            new YarnPackageLock($this->path),
            new BunPackageLock($this->path),
        ];

        foreach ($scanners as $scanner) {
            if ($scanner->canScan()) {
                return $scanner->scan();
            }
        }

        return collect();
    }
}
