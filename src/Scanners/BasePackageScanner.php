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

    protected ?string $resolvedBase = null;

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

    protected function resolvedBase(): string
    {
        return $this->resolvedBase ??= (realpath($this->path) ?: $this->path);
    }

    /**
     * @param  array<string, string>  $dependencies
     */
    protected function processDependencies(array $dependencies, PackageCollection $packages, bool $isDev, ?callable $versionCb = null): void
    {
        $direct = $this->directDependencies();

        foreach ($dependencies as $packageName => $version) {
            if ($packageName === '') {
                continue;
            }

            if ($versionCb !== null) {
                $version = $versionCb($packageName, $version);
            }

            $isDirect = array_key_exists($packageName, $direct);
            $constraint = $isDirect ? $direct[$packageName]['constraint'] : (string) $version;
            $packageIsDev = $isDirect ? $direct[$packageName]['isDev'] : $isDev;

            $packages->push(new Package(
                name: $packageName,
                version: self::normalizeVersion((string) $version),
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
    protected function directDependencies(): array
    {
        if ($this->directPackages !== null) {
            return $this->directPackages;
        }

        $json = self::readJsonFile($this->path.'package.json');
        if ($json === null) {
            return $this->directPackages = [];
        }

        return $this->directPackages = self::collectManifestDeps($json, 'dependencies', 'devDependencies');
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function readJsonFile(string $path): ?array
    {
        if (! file_exists($path) || ! is_readable($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        $json = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($json)) {
            return null;
        }

        /** @var array<string, mixed> $json */
        return $json;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, array{constraint: string, isDev: bool}>
     */
    protected static function collectManifestDeps(array $manifest, string $prodKey, string $devKey): array
    {
        return [
            ...self::collectDeps($manifest[$prodKey] ?? null, false),
            ...self::collectDeps($manifest[$devKey] ?? null, true),
        ];
    }

    /**
     * @return array<string, array{constraint: string, isDev: bool}>
     */
    private static function collectDeps(mixed $deps, bool $isDev): array
    {
        if (! is_array($deps)) {
            return [];
        }

        $collected = [];
        foreach ($deps as $name => $constraint) {
            if (! is_string($name)) {
                continue;
            }
            if (! is_scalar($constraint)) {
                continue;
            }
            $collected[$name] = ['constraint' => (string) $constraint, 'isDev' => $isDev];
        }

        return $collected;
    }

    protected static function normalizeVersion(string $version): string
    {
        return preg_replace('/[^0-9.]/', '', $version) ?? '';
    }

    protected function computePath(string $packageName): string
    {
        return $this->resolvedBase().DIRECTORY_SEPARATOR.'node_modules'.DIRECTORY_SEPARATOR
            .str_replace('/', DIRECTORY_SEPARATOR, $packageName);
    }

    protected function readContents(string $path, string $label = 'Package'): ?string
    {
        if (! file_exists($path) || ! is_readable($path)) {
            Log::warning("Failed to scan {$label}: {$path}");

            return null;
        }

        $contents = file_get_contents($path);

        return $contents === false ? null : $contents;
    }
}
