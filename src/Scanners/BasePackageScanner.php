<?php

namespace Laravel\Roster\Scanners;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Roster\Approach;
use Laravel\Roster\Enums\Approaches;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;

abstract class BasePackageScanner
{
    /**
     * Map of package names to enums
     *
     * @var array<string, Packages|Approaches|array<int, Packages|Approaches>>
     */
    protected array $map = [
        'alpinejs' => Packages::ALPINEJS,
        'eslint' => Packages::ESLINT,
        '@inertiajs/react' => [Packages::INERTIA, Packages::INERTIA_REACT],
        '@inertiajs/svelte' => [Packages::INERTIA, Packages::INERTIA_SVELTE],
        '@inertiajs/vue3' => [Packages::INERTIA, Packages::INERTIA_VUE],
        'laravel-echo' => Packages::ECHO,
        '@laravel/vite-plugin-wayfinder' => [Packages::WAYFINDER, Packages::WAYFINDER_VITE],
        'prettier' => Packages::PRETTIER,
        'react' => Packages::REACT,
        'tailwindcss' => Packages::TAILWINDCSS,
        'vue' => Packages::VUE,
    ];

    /** @var array<string, array{constraint: string, isDev: bool}>|null */
    protected ?array $directPackages = null;

    public function __construct(protected string $path)
    {
        //
    }

    /**
     * @return \Illuminate\Support\Collection<int, \Laravel\Roster\Package|\Laravel\Roster\Approach>
     */
    abstract public function scan(): Collection;

    /**
     * Check if the scanner can handle the given path
     */
    abstract public function canScan(): bool;

    /**
     * Process dependencies and add them to the mapped items collection
     *
     * @param  array<string, string>  $dependencies
     * @param  Collection<int, Package|Approach>  $mappedItems
     * @param  ?callable  $versionCb  - callback to override version
     */
    protected function processDependencies(array $dependencies, Collection $mappedItems, bool $isDev = false, ?callable $versionCb = null): void
    {
        if ($this->directPackages === null) {
            $this->directPackages = $this->direct();
        }

        foreach ($dependencies as $packageName => $version) {
            $mappedPackage = $this->map[$packageName] ?? null;

            if ($mappedPackage === null) {
                continue;
            }

            if (! is_array($mappedPackage)) {
                $mappedPackage = [$mappedPackage];
            }

            if ($versionCb !== null) {
                $version = $versionCb($packageName, $version);
            }

            $this->addMappedPackages($mappedPackage, $packageName, $version, $isDev, $mappedItems);
        }
    }

    /**
     * Add mapped packages to the collection
     *
     * @param  array<int, Packages|Approaches>  $mappedPackage
     * @param  Collection<int, Package|Approach>  $mappedItems
     */
    private function addMappedPackages(array $mappedPackage, string $packageName, string $version, bool $isDev, Collection $mappedItems): void
    {
        $niceVersion = preg_replace('/[^0-9.]/', '', $version) ?? '';
        $directInfo = $this->directPackages[$packageName] ?? null;

        $isDirect = $directInfo !== null;
        $constraint = $isDirect ? $directInfo['constraint'] : $version;
        $packageIsDev = $isDirect ? $directInfo['isDev'] : $isDev;

        foreach ($mappedPackage as $mapped) {
            $mappedItems->push(match (get_class($mapped)) {
                Packages::class => (new Package($mapped, $packageName, $niceVersion, $packageIsDev))
                    ->setDirect($isDirect)
                    ->setConstraint($constraint),
                Approaches::class => new Approach($mapped),
                default => throw new \InvalidArgumentException('Unsupported mapping'),
            });
        }
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

    /**
     * Returns direct dependencies as defined in package.json
     *
     * @return array<string, array{constraint: string, isDev: bool}>
     */
    protected function direct(): array
    {
        $filename = rtrim($this->path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'package.json';

        $contents = $this->validateFile($filename, 'package.json');
        if ($contents === null) {
            return [];
        }

        $json = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($json)) {
            return [];
        }

        /** @var array<string, mixed> $json */
        return $this->extractDependencies($json);
    }

    /**
     * Extract dependencies from parsed package.json
     *
     * @param  array<string, mixed>  $json
     * @return array<string, array{constraint: string, isDev: bool}>
     */
    private function extractDependencies(array $json): array
    {
        $packages = [];

        /** @var array<string, string> $dependencies */
        $dependencies = $json['dependencies'] ?? [];
        foreach ($dependencies as $name => $constraint) {
            $packages[$name] = [
                'constraint' => $constraint,
                'isDev' => false,
            ];
        }

        /** @var array<string, string> $devDependencies */
        $devDependencies = $json['devDependencies'] ?? [];
        foreach ($devDependencies as $name => $constraint) {
            $packages[$name] = [
                'constraint' => $constraint,
                'isDev' => true,
            ];
        }

        return $packages;
    }
}
