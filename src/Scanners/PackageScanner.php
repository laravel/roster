<?php

declare(strict_types=1);

namespace Laravel\Roster\Scanners;

use Illuminate\Container\Container;
use Illuminate\Support\Str;
use Laravel\Roster\Enums\PackageSource;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\Scanners\Concerns\ParsesManifests;
use Throwable;

abstract class PackageScanner
{
    use ParsesManifests;

    /** @var array<string, array{constraint: string, isDev: bool}>|null */
    protected ?array $directPackages = null;

    protected ?string $resolvedBase = null;

    /** @var array<string, mixed>|null */
    protected ?array $manifest = null;

    protected bool $manifestLoaded = false;

    public function __construct(protected string $path)
    {
        $this->path = Str::finish($path, DIRECTORY_SEPARATOR);
    }

    abstract public function scan(): PackageCollection;

    abstract protected function source(): PackageSource;

    abstract protected function manifestFile(): string;

    /**
     * @return array{string, string}
     */
    abstract protected function dependencyKeys(): array;

    abstract protected function computePath(string $packageName): string;

    /**
     * @param  array<string, string>  $dependencies
     */
    protected function processDependencies(array $dependencies, PackageCollection $packages, bool $isDev, bool $authoritative = false): void
    {
        $direct = $this->directDependencies();

        foreach ($dependencies as $packageName => $version) {
            $packageName = (string) $packageName;

            if ($packageName === '') {
                continue;
            }

            $isDirect = array_key_exists($packageName, $direct);

            $packages->push(new Package(
                name: $packageName,
                version: self::normalizeVersion($version),
                source: $this->source(),
                dev: $isDirect && ! $authoritative ? $direct[$packageName]['isDev'] : $isDev,
                direct: $isDirect,
                constraint: $isDirect ? $direct[$packageName]['constraint'] : $version,
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

        $manifest = $this->manifest();

        if ($manifest === null) {
            return $this->directPackages = [];
        }

        [$prodKey, $devKey] = $this->dependencyKeys();

        return $this->directPackages = self::collectManifestDeps($manifest, $prodKey, $devKey);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function manifest(): ?array
    {
        if (! $this->manifestLoaded) {
            $this->manifest = self::readJsonFile($this->path.$this->manifestFile());
            $this->manifestLoaded = true;
        }

        return $this->manifest;
    }

    protected function resolvedBase(): string
    {
        return $this->resolvedBase ??= (realpath($this->path) ?: $this->path);
    }

    protected function readContents(string $path, string $label): ?string
    {
        if (! file_exists($path) || ! is_readable($path)) {
            $this->warn("Failed to read {$label}: {$path}");

            return null;
        }

        $contents = file_get_contents($path);

        return $contents === false ? null : $contents;
    }

    protected function warn(string $message): void
    {
        try {
            $container = Container::getInstance();

            if (! $container->bound('log')) {
                return;
            }

            $logger = $container->make('log');

            if (is_object($logger) && method_exists($logger, 'warning')) {
                $logger->warning($message);
            }
        } catch (Throwable) {
            //
        }
    }
}
