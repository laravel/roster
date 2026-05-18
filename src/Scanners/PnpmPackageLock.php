<?php

namespace Laravel\Roster\Scanners;

use Exception;
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

        /** @var array<string, array<string, mixed>> $importers */
        $importers = $parsed['importers'] ?? [];
        $root = $importers['.'] ?? [];

        /** @var array<string, array<string, mixed>> $rootDeps */
        $rootDeps = $root['dependencies'] ?? [];
        /** @var array<string, array<string, mixed>> $rootDevDeps */
        $rootDevDeps = $root['devDependencies'] ?? [];

        $this->processDependencies($this->extractVersions($rootDeps), $packages, false);
        $this->processDependencies($this->extractVersions($rootDevDeps), $packages, true);

        return $packages;
    }

    /**
     * Pnpm stores each entry as `{ specifier, version }`. We only care about the resolved version.
     *
     * @param  array<string, array<string, mixed>>  $entries
     * @return array<string, string>
     */
    private function extractVersions(array $entries): array
    {
        $versions = [];

        foreach ($entries as $name => $data) {
            if (isset($data['version']) && is_scalar($data['version'])) {
                $versions[$name] = (string) $data['version'];
            }
        }

        return $versions;
    }
}
