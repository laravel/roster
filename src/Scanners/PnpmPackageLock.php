<?php

namespace Laravel\Roster\Scanners;

use Illuminate\Support\Facades\Log;
use Laravel\Roster\PackageCollection;
use Symfony\Component\Yaml\Yaml;

class PnpmPackageLock extends BasePackageScanner
{
    protected function lockFile(): string
    {
        return 'pnpm-lock.yaml';
    }

    public function scan(): PackageCollection
    {
        $packages = new PackageCollection;
        $lockFilePath = $this->lockFilePath();

        $contents = $this->validateFile($lockFilePath, 'PNPM lock');
        if ($contents === null) {
            return $packages;
        }

        try {
            /** @var array<string, mixed> $parsed */
            $parsed = Yaml::parse($contents);
        } catch (\Exception $e) {
            Log::error('Failed to parse YAML: '.$e->getMessage());

            return $packages;
        }

        /** @var array<string, string> $dependencies */
        $dependencies = [];
        /** @var array<string, string> $devDependencies */
        $devDependencies = [];

        /** @var array<string, array<string, mixed>> $importers */
        $importers = $parsed['importers'] ?? [];
        $root = $importers['.'] ?? [];
        /** @var array<string, array<string, mixed>> $rootDependencies */
        $rootDependencies = $root['dependencies'] ?? [];
        /** @var array<string, array<string, mixed>> $rootDevDependencies */
        $rootDevDependencies = $root['devDependencies'] ?? [];

        foreach ($rootDependencies as $name => $data) {
            if (isset($data['version']) && is_scalar($data['version'])) {
                $dependencies[$name] = (string) $data['version'];
            }
        }

        foreach ($rootDevDependencies as $name => $data) {
            if (isset($data['version']) && is_scalar($data['version'])) {
                $devDependencies[$name] = (string) $data['version'];
            }
        }

        $this->processDependencies($dependencies, $packages, false);
        $this->processDependencies($devDependencies, $packages, true);

        return $packages;
    }
}
