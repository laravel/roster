<?php

namespace Laravel\Roster\Scanners;

use Illuminate\Support\Facades\Log;
use Laravel\Roster\Enums\PackageSource;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\Registry;

class Composer
{
    protected string $vendorDir = 'vendor';

    /** @var array<string, array{constraint: string, isDev: bool}> */
    protected array $directPackages = [];

    public function __construct(
        protected string $path,
        protected Registry $registry,
    ) {}

    public function scan(): PackageCollection
    {
        $packages = new PackageCollection;

        if (! file_exists($this->path) || ! is_readable($this->path)) {
            return $packages;
        }

        $contents = file_get_contents($this->path);
        if ($contents === false) {
            return $packages;
        }

        $json = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($json) || ! array_key_exists('packages', $json)) {
            Log::warning('Failed to decode composer.lock: '.$this->path);

            return $packages;
        }

        $this->directPackages = $this->readDirect();

        /** @var array<int, array<string, string>> $prod */
        $prod = $json['packages'] ?? [];
        /** @var array<int, array<string, string>> $dev */
        $dev = $json['packages-dev'] ?? [];

        $this->process($prod, $packages, false);
        $this->process($dev, $packages, true);

        return $packages;
    }

    /**
     * @param  array<int, array<string, string>>  $rawPackages
     */
    private function process(array $rawPackages, PackageCollection $packages, bool $isDev): void
    {
        foreach ($rawPackages as $raw) {
            $name = $raw['name'] ?? '';
            $version = $raw['version'] ?? '';

            if ($name === '') {
                continue;
            }

            $direct = array_key_exists($name, $this->directPackages);
            $constraint = $direct ? $this->directPackages[$name]['constraint'] : $version;
            $packageIsDev = $direct ? $this->directPackages[$name]['isDev'] : $isDev;

            $packages->push(new Package(
                name: $name,
                version: $this->normalizeVersion($version),
                source: PackageSource::COMPOSER,
                alias: $this->registry->aliasFor(PackageSource::COMPOSER, $name),
                dev: $packageIsDev,
                direct: $direct,
                constraint: $constraint,
                path: $this->computePath($name),
            ));
        }
    }

    /**
     * @return array<string, array{constraint: string, isDev: bool}>
     */
    private function readDirect(): array
    {
        $packages = [];
        $dir = dirname($this->path);
        $real = realpath($dir);
        $filename = ($real !== false ? $real : $dir).DIRECTORY_SEPARATOR.'composer.json';

        if (! file_exists($filename) || ! is_readable($filename)) {
            return $packages;
        }

        $contents = file_get_contents($filename);
        if ($contents === false) {
            return $packages;
        }

        $json = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($json)) {
            return $packages;
        }

        $config = $json['config'] ?? [];
        $this->vendorDir = is_array($config) && isset($config['vendor-dir']) && is_string($config['vendor-dir'])
            ? $config['vendor-dir']
            : 'vendor';

        foreach ((array) ($json['require'] ?? []) as $name => $constraint) {
            $packages[$name] = ['constraint' => $constraint, 'isDev' => false];
        }

        foreach ((array) ($json['require-dev'] ?? []) as $name => $constraint) {
            $packages[$name] = ['constraint' => $constraint, 'isDev' => true];
        }

        return $packages;
    }

    private function normalizeVersion(string $version): string
    {
        return preg_replace('/[^0-9.]/', '', $version) ?? '';
    }

    private function computePath(string $packageName): string
    {
        $vendorPath = str_replace('/', DIRECTORY_SEPARATOR, $this->vendorDir);

        $isAbsolute = (DIRECTORY_SEPARATOR === '/' && str_starts_with($vendorPath, DIRECTORY_SEPARATOR))
            || (DIRECTORY_SEPARATOR === '\\' && preg_match('/^[A-Za-z]:[\\\\\\/]/', $vendorPath));

        if ($isAbsolute) {
            return $vendorPath.DIRECTORY_SEPARATOR
                .str_replace('/', DIRECTORY_SEPARATOR, $packageName);
        }

        $real = realpath(dirname($this->path));
        $base = $real !== false ? $real : dirname($this->path);

        return $base.DIRECTORY_SEPARATOR
            .$vendorPath.DIRECTORY_SEPARATOR
            .str_replace('/', DIRECTORY_SEPARATOR, $packageName);
    }
}
