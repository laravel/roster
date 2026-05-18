<?php

namespace Laravel\Roster\Scanners;

use Illuminate\Support\Facades\Log;
use Laravel\Roster\Enums\PackageSource;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\Registry;

abstract class BasePackageScanner
{
    /** @var array<string, array{constraint: string, isDev: bool}>|null */
    protected ?array $directPackages = null;

    public function __construct(
        protected string $path,
        protected Registry $registry,
    ) {}

    abstract protected function lockFile(): string;

    abstract public function scan(): PackageCollection;

    public function canScan(): bool
    {
        return file_exists($this->lockFilePath());
    }

    protected function lockFilePath(): string
    {
        return $this->path.$this->lockFile();
    }

    /**
     * @param  array<string, string>  $dependencies
     */
    protected function processDependencies(array $dependencies, PackageCollection $packages, bool $isDev, ?callable $versionCb = null): void
    {
        $direct = $this->direct();

        foreach ($dependencies as $packageName => $version) {
            if ($packageName === '') {
                continue;
            }

            if (! is_null($versionCb)) {
                $version = $versionCb($packageName, $version);
            }

            $isDirect = false;
            $constraint = (string) $version;
            $packageIsDev = $isDev;

            if (array_key_exists($packageName, $direct)) {
                $isDirect = true;
                $constraint = $direct[$packageName]['constraint'];
                $packageIsDev = $direct[$packageName]['isDev'];
            }

            $packages->push(new Package(
                name: $packageName,
                version: $this->normalizeVersion((string) $version),
                source: PackageSource::NPM,
                alias: $this->registry->aliasFor(PackageSource::NPM, $packageName),
                dev: $packageIsDev,
                direct: $isDirect,
                constraint: $constraint,
                path: $this->computePath($packageName),
            ));
        }
    }

    /**
     * @return array<string, array{constraint: string, isDev: bool}>
     */
    protected function direct(): array
    {
        if ($this->directPackages !== null) {
            return $this->directPackages;
        }

        $this->directPackages = [];
        $filename = $this->path.'package.json';

        if (! file_exists($filename) || ! is_readable($filename)) {
            return $this->directPackages;
        }

        $contents = file_get_contents($filename);
        if ($contents === false) {
            return $this->directPackages;
        }

        $json = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($json)) {
            return $this->directPackages;
        }

        foreach ((array) ($json['dependencies'] ?? []) as $name => $constraint) {
            $this->directPackages[$name] = ['constraint' => (string) $constraint, 'isDev' => false];
        }

        foreach ((array) ($json['devDependencies'] ?? []) as $name => $constraint) {
            $this->directPackages[$name] = ['constraint' => (string) $constraint, 'isDev' => true];
        }

        return $this->directPackages;
    }

    protected function normalizeVersion(string $version): string
    {
        return preg_replace('/[^0-9.]/', '', $version) ?? '';
    }

    protected function computePath(string $packageName): string
    {
        $real = realpath($this->path);
        $base = $real !== false ? $real : $this->path;

        return $base.DIRECTORY_SEPARATOR.'node_modules'.DIRECTORY_SEPARATOR
            .str_replace('/', DIRECTORY_SEPARATOR, $packageName);
    }

    protected function validateFile(string $path, string $type = 'Package'): ?string
    {
        if (! file_exists($path) || ! is_readable($path)) {
            Log::warning("Failed to scan $type: $path");

            return null;
        }

        $contents = file_get_contents($path);

        return $contents === false ? null : $contents;
    }
}
