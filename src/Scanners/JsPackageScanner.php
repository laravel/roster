<?php

declare(strict_types=1);

namespace Laravel\Roster\Scanners;

use Illuminate\Support\Facades\Log;
use Laravel\Roster\Enums\PackageSource;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\Scanners\Concerns\ParsesManifests;

/**
 * Base for the JS lockfile scanners. Constructed with the project base
 * directory; packages resolve under `node_modules` and direct/dev status
 * is read from `package.json`.
 */
abstract class JsPackageScanner
{
    use ParsesManifests;

    /** @var array<string, array{constraint: string, isDev: bool}>|null */
    protected ?array $directPackages = null;

    protected ?string $resolvedBase = null;

    public function __construct(protected string $path) {}

    abstract protected function lockFile(): string;

    abstract public function scan(): PackageCollection;

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
    protected function processDependencies(array $dependencies, PackageCollection $packages, bool $isDev): void
    {
        $direct = $this->directDependencies();

        foreach ($dependencies as $packageName => $version) {
            if ($packageName === '') {
                continue;
            }

            $isDirect = array_key_exists($packageName, $direct);
            $constraint = $isDirect ? $direct[$packageName]['constraint'] : $version;
            $packageIsDev = $isDirect ? $direct[$packageName]['isDev'] : $isDev;

            $packages->push(new Package(
                name: $packageName,
                version: self::normalizeVersion($version),
                source: PackageSource::NPM,
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
