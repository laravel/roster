<?php

declare(strict_types=1);

namespace Laravel\Roster\Scanners;

use Exception;
use Laravel\Roster\PackageCollection;
use Symfony\Component\Yaml\Yaml;

class PnpmPackageLock extends JsPackageScanner
{
    public function scan(): PackageCollection
    {
        $packages = new PackageCollection;
        $lockFilePath = $this->path.'pnpm-lock.yaml';

        $contents = $this->readContents($lockFilePath, 'pnpm-lock.yaml');

        if ($contents === null) {
            return $packages;
        }

        try {
            /** @var array<string, mixed> $parsed */
            $parsed = Yaml::parse($contents);
        } catch (Exception) {
            $this->warn('Failed to parse pnpm-lock.yaml: '.$lockFilePath);

            return $packages;
        }

        /** @var array<string, string> $allPackages */
        $allPackages = [];

        /** @var array<string, mixed> $packagesMap */
        $packagesMap = is_array($parsed['packages'] ?? null) ? $parsed['packages'] : [];

        foreach ($packagesMap as $key => $_) {
            $pair = $this->splitNameAndVersion((string) $key);

            if ($pair === null) {
                continue;
            }

            [$name, $version] = $pair;

            if (isset($allPackages[$name])) {
                continue;
            }

            $allPackages[$name] = $version;
        }

        /** @var array<string, array<string, mixed>> $importers */
        $importers = $parsed['importers'] ?? [];
        $root = $importers['.'] ?? [];

        /** @var array<string, array<string, mixed>> $rootDeps */
        $rootDeps = $root['dependencies'] ?? [];

        /** @var array<string, array<string, mixed>> $rootDevDeps */
        $rootDevDeps = $root['devDependencies'] ?? [];

        foreach ([$rootDeps, $rootDevDeps] as $entries) {
            foreach ($entries as $name => $data) {
                if (isset($data['version']) && is_scalar($data['version'])) {
                    $allPackages[$name] = $this->stripPeerSuffix((string) $data['version']);
                }
            }
        }

        $this->processDependencies($allPackages, $packages, false);

        return $packages;
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function splitNameAndVersion(string $key): ?array
    {
        $key = $this->stripPeerSuffix($key);

        // pnpm v5/v6: `/lodash/4.17.21`, `/@babel/core/7.0.0`.
        if (str_starts_with($key, '/')) {
            $key = substr($key, 1);
            $position = strrpos($key, '/');

            if ($position === false || $position === 0) {
                return null;
            }

            return [substr($key, 0, $position), substr($key, $position + 1)];
        }

        // pnpm v9: `lodash@4.17.21`, `@babel/core@7.0.0`.
        $position = strrpos($key, '@');

        if ($position === false || $position === 0) {
            return null;
        }

        return [substr($key, 0, $position), substr($key, $position + 1)];
    }

    private function stripPeerSuffix(string $value): string
    {
        $paren = strpos($value, '(');

        return $paren === false ? $value : substr($value, 0, $paren);
    }
}
