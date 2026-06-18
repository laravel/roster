<?php

declare(strict_types=1);

namespace Laravel\Roster\Scanners;

use Exception;
use Illuminate\Support\Facades\Log;
use Laravel\Roster\PackageCollection;
use Symfony\Component\Yaml\Yaml;

class PnpmPackageLock extends JsPackageScanner
{
    protected function lockFile(): string
    {
        return 'pnpm-lock.yaml';
    }

    public function scan(): PackageCollection
    {
        $packages = new PackageCollection;
        $lockFilePath = $this->lockFilePath();

        $contents = $this->readContents($lockFilePath, 'PNPM lock');
        if ($contents === null) {
            return $packages;
        }

        try {
            /** @var array<string, mixed> $parsed */
            $parsed = Yaml::parse($contents);
        } catch (Exception $exception) {
            Log::error('Failed to parse YAML: '.$exception->getMessage());

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
                    $allPackages[$name] = (string) $data['version'];
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
        $position = strrpos($key, '@');
        if ($position === false || $position === 0) {
            return null;
        }

        return [substr($key, 0, $position), substr($key, $position + 1)];
    }
}
