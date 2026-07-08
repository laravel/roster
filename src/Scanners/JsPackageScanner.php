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

    protected function source(): PackageSource
    {
        return PackageSource::Npm;
    }

    protected function manifestFile(): string
    {
        return 'package.json';
    }

    /**
     * @return array<string, bool>
     */
    protected function manifestSections(): array
    {
        return [
            'devDependencies' => true,
            'peerDependencies' => false,
            'optionalDependencies' => false,
            'dependencies' => false,
        ];
    }

    protected function computePath(string $packageName): string
    {
        return $this->resolvedBase().DIRECTORY_SEPARATOR.'node_modules'.DIRECTORY_SEPARATOR
            .str_replace('/', DIRECTORY_SEPARATOR, $packageName);
    }
}
