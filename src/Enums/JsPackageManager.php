<?php

declare(strict_types=1);

namespace Laravel\Roster\Enums;

enum JsPackageManager: string
{
    case NPM = 'npm';
    case PNPM = 'pnpm';
    case YARN = 'yarn';
    case BUN = 'bun';

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
            self::NPM => ['package-lock.json'],
            self::PNPM => ['pnpm-lock.yaml'],
            self::YARN => ['yarn.lock'],
            self::BUN => ['bun.lock', 'bun.lockb'],
        };
    }
}
