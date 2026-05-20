<?php

declare(strict_types=1);

namespace Laravel\Roster\Scanners;

use Laravel\Roster\Enums\JsPackageManager;
use Laravel\Roster\PackageCollection;

class JsLockfile
{
    private bool $resolved = false;

    private ?JsPackageManager $resolvedManager = null;

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
        if ($this->resolved) {
            return $this->resolvedManager;
        }

        $this->resolved = true;

        foreach (JsPackageManager::cases() as $case) {
            if (file_exists($this->path.$case->lockFile())) {
                return $this->resolvedManager = $case;
            }
        }

        return null;
    }

    private function scannerFor(JsPackageManager $manager): BasePackageScanner
    {
        return match ($manager) {
            JsPackageManager::NPM => new NpmPackageLock($this->path),
            JsPackageManager::PNPM => new PnpmPackageLock($this->path),
            JsPackageManager::YARN => new YarnPackageLock($this->path),
            JsPackageManager::BUN => new BunPackageLock($this->path),
        };
    }
}
