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
        'tailwindcss' => [Packages::TAILWINDCSS],
        'vue' => Packages::VUE,
    ];

    /** @var array<string, array{constraint: string, isDev: bool}> */
    protected array $directPackages = [];

    public function __construct(protected string $path) {}

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
        if ($this->directPackages === []) {
            $this->directPackages = $this->direct();
        }

        foreach ($dependencies as $packageName => $version) {
            $mappedPackage = $this->map[$packageName] ?? null;
            if (is_null($mappedPackage)) {
                continue;
            }

            if (! is_array($mappedPackage)) {
                $mappedPackage = [$mappedPackage];
            }

            if (! is_null($versionCb)) {
                $version = $versionCb($packageName, $version);
            }

            foreach ($mappedPackage as $mapped) {
                $niceVersion = preg_replace('/[^0-9.]/', '', $version) ?? '';
                $direct = false;
                $constraint = $version;
                $packageIsDev = $isDev;

                if (array_key_exists($packageName, $this->directPackages)) {
                    $direct = true;
                    $constraint = $this->directPackages[$packageName]['constraint'];
                    $packageIsDev = $this->directPackages[$packageName]['isDev'];
                }

                $mappedItems->push(match (get_class($mapped)) {
                    Packages::class => (new Package($mapped, $packageName, $niceVersion, $packageIsDev))
                        ->setDirect($direct)
                        ->setConstraint($constraint),
                    Approaches::class => new Approach($mapped),
                    default => throw new \InvalidArgumentException('Unsupported mapping')
                });
            }
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
        $packages = [];
        $filename = $this->path . 'package.json';
        if (file_exists($filename) === false || is_readable($filename) === false) {
            return $packages;
        }

        $json = file_get_contents($filename);
        if ($json === false) {
            return $packages;
        }

        $json = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($json)) {
            return $packages;
        }

        foreach (($json['dependencies'] ?? []) as $name => $constraint) {
            $packages[$name] = [
                'constraint' => (string) $constraint,
                'isDev' => false,
            ];
        }

        foreach (($json['devDependencies'] ?? []) as $name => $constraint) {
            $packages[$name] = [
                'constraint' => (string) $constraint,
                'isDev' => true,
            ];
        }

        return $packages;
    }
}
