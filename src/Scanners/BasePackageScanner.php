<?php

namespace Laravel\Roster\Scanners;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Roster\Approach;
use Laravel\Roster\Enums\Approaches;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Enums\PackageSource;
use Laravel\Roster\Package;

abstract class BasePackageScanner
{
    /**
     * Map of package names to enums
     *
     * @var array<string, Packages|Approaches>
     */
    protected array $map = [
        'alpinejs' => Packages::ALPINEJS,
        'eslint' => Packages::ESLINT,
        '@inertiajs/react' => Packages::INERTIA_REACT,
        '@inertiajs/svelte' => Packages::INERTIA_SVELTE,
        '@inertiajs/vue3' => Packages::INERTIA_VUE,
        'laravel-echo' => Packages::ECHO,
        '@laravel/vite-plugin-wayfinder' => Packages::WAYFINDER_VITE,
        'prettier' => Packages::PRETTIER,
        'react' => Packages::REACT,
        'tailwindcss' => Packages::TAILWINDCSS,
        'vue' => Packages::VUE,
    ];

    /** @var array<string, array{constraint: string, isDev: bool}>|null */
    protected ?array $directPackages = null;

    public function __construct(protected string $path) {}

    /**
     * Returns the expected lock file name
     */
    abstract protected function lockFile(): string;

    /**
     * @return \Illuminate\Support\Collection<int, \Laravel\Roster\Package|\Laravel\Roster\Approach>
     */
    abstract public function scan(): Collection;

    /**
     * Check if the scanner can handle the given path
     */
    public function canScan(): bool
    {
        return file_exists($this->lockFilePath());
    }

    /**
     * Get the file path of the lock file
     */
    protected function lockFilePath(): string
    {
        return $this->path.$this->lockFile();
    }

    /**
     * Process dependencies and add them to the mapped items collection
     *
     * @param  array<string, string>  $dependencies
     * @param  Collection<int, Package|Approach>  $mappedItems
     * @param  ?callable  $versionCb  - callback to override version
     */
    protected function processDependencies(array $dependencies, Collection $mappedItems, bool $isDev, ?callable $versionCb = null): void
    {
        $directPackages = $this->direct();

        foreach ($dependencies as $packageName => $version) {
            $mappedPackage = $this->map[$packageName] ?? null;
            if (is_null($mappedPackage)) {
                continue;
            }

            if (! is_null($versionCb)) {
                $version = $versionCb($packageName, $version);
            }

            $direct = false;
            $constraint = $version;
            $packageIsDev = $isDev;

            if (array_key_exists($packageName, $directPackages)) {
                $direct = true;
                $constraint = $directPackages[$packageName]['constraint'];
                $packageIsDev = $directPackages[$packageName]['isDev'];
            }

            $niceVersion = preg_replace('/[^0-9.]/', '', $version) ?? '';
            $mappedItems->push(match (get_class($mappedPackage)) {
                Packages::class => (new Package($mappedPackage, $packageName, $niceVersion, $packageIsDev))->setDirect($direct)->setConstraint($constraint)->setSource(PackageSource::NPM)->setPath($this->computePath($packageName)),
                Approaches::class => new Approach($mappedPackage),
                default => throw new \InvalidArgumentException('Unsupported mapping')
            });
        }
    }

    /**
     * Returns direct dependencies as defined in package.json
     *
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
            $this->directPackages[$name] = [
                'constraint' => $constraint,
                'isDev' => false,
            ];
        }

        foreach ((array) ($json['devDependencies'] ?? []) as $name => $constraint) {
            $this->directPackages[$name] = [
                'constraint' => $constraint,
                'isDev' => true,
            ];
        }

        return $this->directPackages;
    }

    protected function computePath(string $packageName): string
    {
        $basePath = realpath($this->path) ?: $this->path;

        return $basePath.DIRECTORY_SEPARATOR.'node_modules'.DIRECTORY_SEPARATOR
            .str_replace('/', DIRECTORY_SEPARATOR, $packageName);
    }

    /**
     * Common file validation logic
     */
    protected function validateFile(string $path, string $type = 'Package'): ?string
    {
        if (! file_exists($path)) {
            Log::warning("Failed to scan $type: $path");

            return null;
        }

        if (! is_readable($path)) {
            Log::warning("File not readable: $path");

            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            Log::warning("Failed to read $type: $path");

            return null;
        }

        return $contents;
    }
}
