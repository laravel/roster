<?php

namespace Laravel\Roster\Scanners;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Roster\Approach;
use Laravel\Roster\Enums\Approaches;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;
use Symfony\Component\Yaml\Yaml;

class PackageLock
{
    /**
     * Map of npm / package.json package names to enums
     *
     * @var array<string, Packages|Approaches|array<int, Packages|Approaches>>
     */
    protected array $map = [
        'alpinejs' => Packages::ALPINEJS,
        '@inertiajs/react' => [Packages::INERTIA, Packages::INERTIA_REACT],
        '@inertiajs/svelte' => [Packages::INERTIA, Packages::INERTIA_SVELTE],
        '@inertiajs/vue3' => [Packages::INERTIA, Packages::INERTIA_VUE],
        'laravel-echo' => Packages::ECHO,
        '@laravel/vite-plugin-wayfinder' => [Packages::WAYFINDER, Packages::WAYFINDER_VITE],
        'react' => Packages::REACT,
        'tailwindcss' => [Packages::TAILWINDCSS],
    ];

    /**
     * @param  string  $path  - Base path to scan for lock files (package-lock.json, pnpm-lock.yaml, yarn.lock)
     */
    public function __construct(protected string $path) {}

    /**
     * @return \Illuminate\Support\Collection<int, \Laravel\Roster\Package|\Laravel\Roster\Approach>
     */
    public function scan(): Collection
    {
        $mappedItems = collect([]);

        // Check for lock files in priority order: npm -> pnpm -> yarn
        $lockFilePaths = [
            $this->path.'package-lock.json',
            $this->path.'pnpm-lock.yaml',
            $this->path.'yarn.lock',
        ];

        foreach ($lockFilePaths as $lockFilePath) {
            if (file_exists($lockFilePath)) {
                $fileName = basename($lockFilePath);

                return match ($fileName) {
                    'package-lock.json' => $this->scanPackageLockJson($lockFilePath),
                    'pnpm-lock.yaml' => $this->scanPnpmLock($lockFilePath),
                    'yarn.lock' => $this->scanYarnLock($lockFilePath),
                    default => collect()
                };
            }
        }

        Log::warning('No Node.js lock file found in: '.$this->path);
        return $mappedItems;
    }

    /**
     * Scan package-lock.json file
     *
     * @param  string  $path
     * @return \Illuminate\Support\Collection<int, \Laravel\Roster\Package|\Laravel\Roster\Approach>
     */
    protected function scanPackageLockJson(string $path): Collection
    {
        $mappedItems = collect([]);

        if (! file_exists($path)) {
            Log::warning('Failed to scan Package: '.$path);

            return $mappedItems;
        }

        if (! is_readable($path)) {
            Log::warning('File not readable: '.$path);

            return $mappedItems;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            Log::warning('Failed to read Package: '.$path);

            return $mappedItems;
        }

        $json = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($json)) {
            Log::warning('Failed to decode Package: '.$path.'. '.json_last_error_msg());

            return $mappedItems;
        }

        if (! array_key_exists('packages', $json)) {
            Log::warning('Malformed package-lock');

            return $mappedItems;
        }

        $dependencies = $json['packages']['']['dependencies'] ?? [];
        $devDependencies = $json['packages']['']['devDependencies'] ?? [];

        $this->processDependencies($dependencies, $mappedItems, false);
        $this->processDependencies($devDependencies, $mappedItems, true);

        return $mappedItems;
    }

    /**
     * Process dependencies and add them to the mapped items collection
     *
     * @param  array<string, string>  $dependencies
     * @param  Collection<int, Package|Approach>  $mappedItems
     */
    private function processDependencies(array $dependencies, Collection $mappedItems, bool $isDev): void
    {
        foreach ($dependencies as $packageName => $version) {
            $mappedPackage = $this->map[$packageName] ?? null;
            if (is_null($mappedPackage)) {
                continue;
            }

            if (! is_array($mappedPackage)) {
                $mappedPackage = [$mappedPackage];
            }

            foreach ($mappedPackage as $mapped) {
                $niceVersion = preg_replace('/[^0-9.]/', '', $version) ?? '';
                $mappedItems->push(match (get_class($mapped)) {
                    Packages::class => new Package($mapped, $niceVersion, $isDev),
                    Approaches::class => new Approach($mapped),
                    default => throw new \InvalidArgumentException('Unsupported mapping')
                });
            }
        }
    }

    /**
     * Scan pnpm-lock.yaml file
     *
     * @param  string  $path
     * @return \Illuminate\Support\Collection<int, \Laravel\Roster\Package|\Laravel\Roster\Approach>
     */
    protected function scanPnpmLock(string $path): Collection
    {
        $mappedItems = collect();

        if (! is_readable($path)) {
            Log::warning('File not readable: ' . $path);
            return $mappedItems;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            Log::warning('Failed to read PNPM lock: ' . $path);
            return $mappedItems;
        }

        try {
            /** @var array<string, mixed> $parsed */
            $parsed = Yaml::parse($contents);
        } catch (\Exception $e) {
            Log::error('Failed to parse YAML: ' . $e->getMessage());
            return $mappedItems;
        }

        /** @var array<string, string> $dependencies */
        $dependencies = [];
        /** @var array<string, string> $devDependencies */
        $devDependencies = [];

        /** @var array<string, array<string, mixed>> $importers */
        $importers = $parsed['importers'] ?? [];
        $root = $importers['.'] ?? [];
        /** @var array<string, array<string, mixed>> $rootDependencies */
        $rootDependencies = $root['dependencies'] ?? [];
        /** @var array<string, array<string, mixed>> $rootDevDependencies */
        $rootDevDependencies = $root['devDependencies'] ?? [];

        foreach ($rootDependencies as $name => $data) {
            if (isset($data['version'])) {
                $dependencies[$name] = $data['version'];
            }
        }

        foreach ($rootDevDependencies as $name => $data) {
            if (isset($data['version'])) {
                $devDependencies[$name] = $data['version'];
            }
        }

        /** @var array<string, string> $dependencies */
        /** @var array<string, string> $devDependencies */
        $this->processDependencies($dependencies, $mappedItems, false);
        $this->processDependencies($devDependencies, $mappedItems, true);

        return $mappedItems;
    }

    /**
     * Scan yarn.lock file
     *
     * @param  string  $path
     * @return \Illuminate\Support\Collection<int, \Laravel\Roster\Package|\Laravel\Roster\Approach>
     */
    protected function scanYarnLock(string $path): Collection
    {
        $mappedItems = collect([]);

        if (!is_readable($path)) {
            Log::warning('File not readable: ' . $path);
            return $mappedItems;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            Log::warning('Failed to read Yarn lock: ' . $path);
            return $mappedItems;
        }

        $dependencies = [];
        $lines = explode("\n", $contents);
        $currentPackage = null;

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Package header line (e.g. lodash@^4.17.21:)
            if (preg_match('/^("?)([^@"]+)(@[^:]+)?:\1$/', $line, $matches)) {
                $currentPackage = $matches[2];
            }
            // Version line
            elseif ($currentPackage && preg_match('/^version\s+"?([^"]+)"?$/', $line, $matches)) {
                $version = $matches[1];
                $dependencies[$currentPackage] = $version;
                $currentPackage = null; // Reset until next package block
            }
        }

        // Yarn lock does not distinguish devDependencies
        $this->processDependencies($dependencies, $mappedItems, false);

        return $mappedItems;
    }
}
