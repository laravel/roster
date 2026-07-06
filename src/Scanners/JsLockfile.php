<?php

declare(strict_types=1);

namespace Laravel\Roster\Scanners;

use Laravel\Roster\Enums\JsPackageManager;
use Laravel\Roster\PackageCollection;

class JsLockfile
{
    public function __construct(protected string $path) {}

    public function scan(): PackageCollection
    {
        $manager = $this->committedManager();

        if (! $manager instanceof JsPackageManager) {
            return (new PackageJson($this->path))->scan();
        }

        return $this->scannerFor($manager)->scan();
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
            JsPackageManager::NPM => new NpmPackageLock($this->path),
            JsPackageManager::PNPM => new PnpmPackageLock($this->path),
            JsPackageManager::YARN => new YarnPackageLock($this->path),
            JsPackageManager::BUN => new BunPackageLock($this->path),
        };
    }
}
