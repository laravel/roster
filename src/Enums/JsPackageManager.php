<?php

namespace Laravel\Roster\Enums;

enum JsPackageManager: string
{
    case NPM = 'npm';
    case PNPM = 'pnpm';
    case YARN = 'yarn';
    case BUN = 'bun';

    public function lockFile(): string
    {
        return match ($this) {
            self::NPM => 'package-lock.json',
            self::PNPM => 'pnpm-lock.yaml',
            self::YARN => 'yarn.lock',
            self::BUN => 'bun.lock',
        };
    }

    public function binary(): string
    {
        return $this->value;
    }

    public function is(self $value): bool
    {
        return $this === $value;
    }
}
