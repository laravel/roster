<?php

declare(strict_types=1);

namespace Laravel\Roster\Enums;

enum JsPackageManager: string
{
    case Npm = 'npm';
    case Pnpm = 'pnpm';
    case Yarn = 'yarn';
    case Bun = 'bun';

    public function lockFile(): string
    {
        return $this->lockFiles()[0];
    }

    /**
     * @return list<string>
     */
    public function lockFiles(): array
    {
        return match ($this) {
            self::Npm => ['package-lock.json'],
            self::Pnpm => ['pnpm-lock.yaml'],
            self::Yarn => ['yarn.lock'],
            self::Bun => ['bun.lock', 'bun.lockb'],
        };
    }
}
