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
            $this->markFailed();

            return $packages;
        }

        try {
            /** @var array<string, mixed> $parsed */
            $parsed = Yaml::parse($contents);
        } catch (Exception) {
            $this->warn('Failed to parse pnpm-lock.yaml: '.$lockFilePath);

            $this->markFailed();

            return $packages;
        }

        /** @var array<string, string> $allPackages */
        $allPackages = [];

        /** @var array<string, mixed> $packagesMap */
        $packagesMap = is_array($parsed['packages'] ?? null) ? $parsed['packages'] : [];

        $lockfileVersion = $parsed['lockfileVersion'] ?? '';
        $slashStyle = is_scalar($lockfileVersion) && str_starts_with((string) $lockfileVersion, '5');

        foreach ($packagesMap as $key => $_) {
            $pair = $this->splitNameAndVersion((string) $key, $slashStyle);

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

        /** @var array<string, mixed> $root */
        $root = $importers['.'] ?? $parsed;

        /** @var array<string, mixed> $rootDeps */
        $rootDeps = is_array($root['dependencies'] ?? null) ? $root['dependencies'] : [];

        /** @var array<string, mixed> $rootDevDeps */
        $rootDevDeps = is_array($root['devDependencies'] ?? null) ? $root['devDependencies'] : [];

        foreach ([$rootDeps, $rootDevDeps] as $entries) {
            foreach ($entries as $name => $data) {
                $version = is_array($data) ? ($data['version'] ?? null) : $data;

                if (is_scalar($version)) {
                    $allPackages[(string) $name] = $this->stripPeerSuffix((string) $version);
                }
            }
        }

        $this->processDependencies($allPackages, $packages, false);

        return $packages;
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function splitNameAndVersion(string $key, bool $slashStyle): ?array
    {
        $key = ltrim($this->stripPeerSuffix($key), '/');

        // pnpm v5: `lodash/4.17.21`, `@babel/core/7.0.0_react@16.8.0`.
        if ($slashStyle) {
            $position = strrpos($key, '/');

            if ($position === false || $position === 0) {
                return null;
            }

            $version = substr($key, $position + 1);
            $peer = strpos($version, '_');

            return [substr($key, 0, $position), $peer === false ? $version : substr($version, 0, $peer)];
        }

        // pnpm v6: `lodash@4.17.21`, v9: `@babel/core@7.0.0`.
        $position = strrpos($key, '@');

        if ($position !== false && $position > 0) {
            return [substr($key, 0, $position), substr($key, $position + 1)];
        }

        $position = strrpos($key, '/');

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
