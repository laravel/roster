<?php

namespace Laravel\Roster\Scanners;

use Illuminate\Support\Collection;
use Laravel\Roster\Approach;
use Laravel\Roster\Enums\Approaches;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;

class PackageLock
{
    /**
     * Map of npm / package.json package names to enums
     *
     * @var array<string, Packages|Approaches|array<int, Packages|Approaches>>
     */
    protected array $map = [
        'tailwindcss' => [Packages::TAILWINDCSS],
        '@inertiajs/vue3' => [Packages::INERTIA, Packages::INERTIA_VUE],
        '@inertiajs/react' => [Packages::INERTIA, Packages::INERTIA_REACT],
        '@inertiajs/svelte' => [Packages::INERTIA, Packages::INERTIA_SVELTE],
        'alpinejs' => Packages::ALPINEJS,
        'laravel-echo' => Packages::ECHO,
        'react' => Packages::REACT,
    ];

    /**
     * @param  string  $path  - package-lock.json
     */
    public function __construct(protected string $path) {}

    /**
     * @return \Illuminate\Support\Collection<int, \Laravel\Roster\Package|\Laravel\Roster\Approach>
     */
    public function scan(): Collection
    {
        $mappedItems = collect([]);

        if (! file_exists($this->path)) {
            error_log('Failed to scan Package: '.$this->path);

            return $mappedItems;
        }

        if (! is_readable($this->path)) {
            error_log('File not readable: '.$this->path);

            return $mappedItems;
        }

        $contents = file_get_contents($this->path);
        if ($contents === false) {
            error_log('Failed to read Package: '.$this->path);

            return $mappedItems;
        }

        $json = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($json)) {
            error_log('Failed to decode Package: '.$this->path.'. '.json_last_error_msg());

            return $mappedItems;
        }

        if (! array_key_exists('packages', $json)) {
            error_log('Malformed package-lock');

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
}
