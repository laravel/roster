<?php

declare(strict_types=1);

namespace Laravel\Roster\Scanners;

use Illuminate\Support\Facades\Log;
use Laravel\Roster\Enums\PackageSource;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\Scanners\Concerns\ParsesManifests;

class Composer
{
    use ParsesManifests;

    protected string $vendorDir = 'vendor';

    protected ?string $resolvedBase = null;

    /** @var array<string, array{constraint: string, isDev: bool}>|null */
    protected ?array $directPackages = null;

    public function __construct(protected string $lockFilePath) {}

    public function scan(): PackageCollection
    {
        $packages = new PackageCollection;

        $json = self::readJsonFile($this->lockFilePath);
        if ($json === null || ! array_key_exists('packages', $json)) {
            if (file_exists($this->lockFilePath)) {
                Log::warning('Failed to decode composer.lock: '.$this->lockFilePath);
            }

            return $packages;
        }

        $direct = $this->directDependencies();

        /** @var array<int, array<string, string>> $prod */
        $prod = $json['packages'] ?? [];
        /** @var array<int, array<string, string>> $dev */
        $dev = $json['packages-dev'] ?? [];

        $this->pushPackages($prod, $packages, false, $direct);
        $this->pushPackages($dev, $packages, true, $direct);

        return $packages;
    }

    /**
     * @param  array<int, array<string, string>>  $rawPackages
     * @param  array<string, array{constraint: string, isDev: bool}>  $direct
     */
    private function pushPackages(array $rawPackages, PackageCollection $packages, bool $isDev, array $direct): void
    {
        foreach ($rawPackages as $raw) {
            $name = $raw['name'] ?? '';
            $version = $raw['version'] ?? '';

            if ($name === '') {
                continue;
            }

            $isDirect = array_key_exists($name, $direct);
            $constraint = $isDirect ? $direct[$name]['constraint'] : $version;
            $packageIsDev = $isDirect ? $direct[$name]['isDev'] : $isDev;

            $packages->push(new Package(
                name: $name,
                version: self::normalizeVersion($version),
                source: PackageSource::COMPOSER,
                dev: $packageIsDev,
                direct: $isDirect,
                constraint: $constraint,
                path: $this->computePath($name),
            ));
        }
    }

    /**
     * @return array<string, array{constraint: string, isDev: bool}>
     */
    private function directDependencies(): array
    {
        if ($this->directPackages !== null) {
            return $this->directPackages;
        }

        $json = self::readJsonFile($this->resolvedBase().DIRECTORY_SEPARATOR.'composer.json');
        if ($json === null) {
            return $this->directPackages = [];
        }

        $config = $json['config'] ?? null;
        if (is_array($config) && isset($config['vendor-dir']) && is_string($config['vendor-dir'])) {
            $this->vendorDir = $config['vendor-dir'];
        }

        return $this->directPackages = self::collectManifestDeps($json, 'require', 'require-dev');
    }

    private function resolvedBase(): string
    {
        if ($this->resolvedBase !== null) {
            return $this->resolvedBase;
        }

        $dir = dirname($this->lockFilePath);

        return $this->resolvedBase = realpath($dir) ?: $dir;
    }

    private function computePath(string $packageName): string
    {
        $vendorPath = str_replace('/', DIRECTORY_SEPARATOR, $this->vendorDir);
        $packageSegment = str_replace('/', DIRECTORY_SEPARATOR, $packageName);

        if ($this->isAbsolutePath($vendorPath)) {
            return $vendorPath.DIRECTORY_SEPARATOR.$packageSegment;
        }

        return $this->resolvedBase().DIRECTORY_SEPARATOR.$vendorPath.DIRECTORY_SEPARATOR.$packageSegment;
    }

    private function isAbsolutePath(string $path): bool
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return str_starts_with($path, '/');
        }

        return (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }
}
