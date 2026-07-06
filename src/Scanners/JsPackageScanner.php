<?php

declare(strict_types=1);

namespace Laravel\Roster\Scanners;

use Laravel\Roster\Enums\PackageSource;

abstract class JsPackageScanner extends PackageScanner
{
    protected bool $failed = false;

    public function failed(): bool
    {
        return $this->failed;
    }

    protected function markFailed(): void
    {
        $this->failed = true;
    }

    protected function source(): PackageSource
    {
        return PackageSource::NPM;
    }

    protected function manifestFile(): string
    {
        return 'package.json';
    }

    /**
     * @return array{string, string}
     */
    protected function dependencyKeys(): array
    {
        return ['dependencies', 'devDependencies'];
    }

    protected function computePath(string $packageName): string
    {
        return $this->resolvedBase().DIRECTORY_SEPARATOR.'node_modules'.DIRECTORY_SEPARATOR
            .str_replace('/', DIRECTORY_SEPARATOR, $packageName);
    }
}
