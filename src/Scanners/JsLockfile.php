<?php

namespace Laravel\Roster\Scanners;

use Laravel\Roster\Enums\JsPackageManager;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\Registry;

class JsLockfile
{
    public function __construct(
        protected string $path,
        protected Registry $registry,
    ) {}

    public function scan(): PackageCollection
    {
        $scanner = $this->resolveScanner();

        if ($scanner !== null) {
            return $scanner->scan();
        }

        return (new PackageJson($this->path, $this->registry))->scan();
    }

    public function committedManager(): ?JsPackageManager
    {
        foreach (JsPackageManager::cases() as $case) {
            if (file_exists($this->path.$case->lockFile())) {
                return $case;
            }
        }

        return null;
    }

    private function resolveScanner(): ?BasePackageScanner
    {
        foreach (JsPackageManager::cases() as $case) {
            $scanner = match ($case) {
                JsPackageManager::NPM => new NpmPackageLock($this->path, $this->registry),
                JsPackageManager::PNPM => new PnpmPackageLock($this->path, $this->registry),
                JsPackageManager::YARN => new YarnPackageLock($this->path, $this->registry),
                JsPackageManager::BUN => new BunPackageLock($this->path, $this->registry),
            };

            if ($scanner->canScan()) {
                return $scanner;
            }
        }

        return null;
    }
}
