<?php

declare(strict_types=1);

namespace Laravel\Roster\Scanners;

use Laravel\Roster\Enums\PackageSource;
use Laravel\Roster\PackageCollection;

class ComposerLock extends PackageScanner
{
    public function scan(): PackageCollection
    {
        $packages = new PackageCollection;

        $json = $this->readJsonOrWarn('composer.lock');

        if ($json === null) {
            return $packages;
        }

        if (! is_array($json['packages'] ?? null)) {
            $this->warn('Malformed composer.lock (missing "packages" key): '.$this->path.'composer.lock');
        }

        $this->processDependencies($this->versions($json['packages'] ?? null), $packages, false, authoritative: true);
        $this->processDependencies($this->versions($json['packages-dev'] ?? null), $packages, true, authoritative: true);

        return $packages;
    }

    protected function source(): PackageSource
    {
        return PackageSource::Composer;
    }

    protected function manifestFile(): string
    {
        return 'composer.json';
    }

    /**
     * @return array<string, bool>
     */
    protected function manifestSections(): array
    {
        return [
            'require-dev' => true,
            'require' => false,
        ];
    }

    protected function computePath(string $packageName): string
    {
        $vendorPath = str_replace('/', DIRECTORY_SEPARATOR, $this->vendorDir());
        $packageSegment = str_replace('/', DIRECTORY_SEPARATOR, $packageName);

        if ($this->isAbsolutePath($vendorPath)) {
            return $vendorPath.DIRECTORY_SEPARATOR.$packageSegment;
        }

        return $this->resolvedBase().DIRECTORY_SEPARATOR.$vendorPath.DIRECTORY_SEPARATOR.$packageSegment;
    }

    /**
     * @return array<string, string>
     */
    private function versions(mixed $rawPackages): array
    {
        if (! is_array($rawPackages)) {
            return [];
        }

        $versions = [];

        foreach ($rawPackages as $raw) {
            if (! is_array($raw)) {
                continue;
            }

            $name = $raw['name'] ?? null;

            if (! is_string($name)) {
                continue;
            }

            if ($name === '') {
                continue;
            }

            $version = $raw['version'] ?? null;

            $versions[$name] = is_string($version) ? $version : '';
        }

        return $versions;
    }

    private function vendorDir(): string
    {
        $config = $this->manifest()['config'] ?? null;

        if (is_array($config) && isset($config['vendor-dir']) && is_string($config['vendor-dir'])) {
            return $config['vendor-dir'];
        }

        return 'vendor';
    }

    private function isAbsolutePath(string $path): bool
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return str_starts_with($path, '/');
        }

        return (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }
}
