<?php

declare(strict_types=1);

namespace Laravel\Roster\Scanners;

use Laravel\Roster\Enums\JsPackageManager;
use Laravel\Roster\PackageCollection;

class JsLockfile
{
    public function __construct(protected string $path)
    {
        //
    }

    public function scan(): PackageCollection
    {
        $manager = $this->committedManager();

        if (! $manager instanceof JsPackageManager || ! file_exists($this->path.$manager->lockFile())) {
            return (new PackageJson($this->path))->scan();
        }

        $scanner = $this->scannerFor($manager);
        $packages = $scanner->scan();

        return $scanner->failed() ? (new PackageJson($this->path))->scan() : $packages;
    }

    public function committedManager(): ?JsPackageManager
    {
        foreach (JsPackageManager::cases() as $case) {
            foreach ($case->lockFiles() as $lockFile) {
                if (file_exists($this->path.$lockFile)) {
                    return $case;
                }
            }
        }

        return null;
    }

    private function scannerFor(JsPackageManager $manager): JsPackageScanner
    {
        return match ($manager) {
            JsPackageManager::Npm => new NpmPackageLock($this->path),
            JsPackageManager::Pnpm => new PnpmPackageLock($this->path),
            JsPackageManager::Yarn => new YarnPackageLock($this->path),
            JsPackageManager::Bun => new BunPackageLock($this->path),
        };
    }
}
