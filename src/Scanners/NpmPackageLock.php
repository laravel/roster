<?php

declare(strict_types=1);

namespace Laravel\Roster\Scanners;

use Laravel\Roster\PackageCollection;

class NpmPackageLock extends JsPackageScanner
{
    public function scan(): PackageCollection
    {
        $packages = new PackageCollection;

        $json = $this->readJsonOrWarn('package-lock.json');

        if ($json === null) {
            $this->failed = true;

            return $packages;
        }

        if (! is_array($json['packages'] ?? null)) {
            $this->warn('Unsupported package-lock.json (missing "packages" key): '.$this->path.'package-lock.json');

            $this->failed = true;

            return $packages;
        }

        /** @var array<string, array<string, mixed>> $jsonPackages */
        $jsonPackages = $json['packages'];

        /** @var array<string, string> $prodPackages */
        $prodPackages = [];

        /** @var array<string, string> $devPackages */
        $devPackages = [];

        foreach ($this->entriesByDepth($jsonPackages) as [$name, $entry]) {
            if (isset($prodPackages[$name])) {
                continue;
            }

            if (isset($devPackages[$name])) {
                continue;
            }

            $version = isset($entry['version']) && is_scalar($entry['version']) ? (string) $entry['version'] : '';

            if (($entry['dev'] ?? false) === true) {
                $devPackages[$name] = $version;
            } else {
                $prodPackages[$name] = $version;
            }
        }

        $this->processDependencies($prodPackages, $packages, false, authoritative: true);
        $this->processDependencies($devPackages, $packages, true, authoritative: true);

        return $packages;
    }

    /**
     * @param  array<string, array<string, mixed>>  $jsonPackages
     * @return list<array{0: string, 1: array<string, mixed>}>
     */
    private function entriesByDepth(array $jsonPackages): array
    {
        $topLevel = [];
        $nested = [];

        foreach ($jsonPackages as $key => $entry) {
            $key = (string) $key;
            $name = $this->nameFromNodeModulesPath($key);

            if ($name === null) {
                continue;
            }

            if (substr_count($key, 'node_modules/') === 1) {
                $topLevel[] = [$name, $entry];
            } else {
                $nested[] = [$name, $entry];
            }
        }

        return array_merge($topLevel, $nested);
    }

    private function nameFromNodeModulesPath(string $key): ?string
    {
        $marker = 'node_modules/';
        $position = strrpos($key, $marker);

        if ($position === false || ! str_starts_with($key, $marker)) {
            return null;
        }

        $name = substr($key, $position + strlen($marker));

        return $name === '' ? null : $name;
    }
}
