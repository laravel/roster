<?php

namespace Laravel\Roster\Scanners;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Roster\Approach;
use Laravel\Roster\Enums\Approaches;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;

class Composer
{
    /**
     * Map of composer package names to enums
     *
     * @var array<string, Packages|Approaches|array<int, Packages|Approaches>|null>
     */
    protected array $map = [
        'filament/filament' => Packages::FILAMENT,
        'inertiajs/inertia-laravel' => [Packages::INERTIA, Packages::INERTIA_LARAVEL],
        'larastan/larastan' => Packages::LARASTAN,
        'laravel/folio' => Packages::FOLIO,
        'laravel/framework' => Packages::LARAVEL,
        'laravel/nightwatch' => Packages::NIGHTWATCH,
        'laravel/nova' => Packages::NOVA,
        'laravel/octane' => Packages::OCTANE,
        'laravel/pennant' => Packages::PENNANT,
        'laravel/pint' => Packages::PINT,
        'laravel/prompts' => Packages::PROMPTS,
        'laravel/reverb' => Packages::REVERB,
        'laravel/scout' => Packages::SCOUT,
        'livewire/flux' => Packages::FLUXUI_FREE,
        'livewire/flux-pro' => Packages::FLUXUI_PRO,
        'livewire/livewire' => Packages::LIVEWIRE,
        'pestphp/pest' => Packages::PEST,
        'rector/rector' => Packages::RECTOR,
        'statamic/cms' => Packages::STATAMIC,
        'livewire/volt' => Packages::VOLT,
        'laravel/wayfinder' => [Packages::WAYFINDER, Packages::WAYFINDER_LARAVEL],
        'tightenco/ziggy' => Packages::ZIGGY,
    ];

    /**
     * @param  string  $path  - composer.lock
     */
    public function __construct(protected string $path) {}

    /**
     * @return \Illuminate\Support\Collection<int, \Laravel\Roster\Package|\Laravel\Roster\Approach>
     */
    public function scan(): Collection
    {
        $mappedItems = collect([]);

        if (! file_exists($this->path)) {
            Log::warning('Failed to scan Composer: '.$this->path);

            return $mappedItems;
        }

        if (! is_readable($this->path)) {
            Log::warning('File not readable: '.$this->path);

            return $mappedItems;
        }

        $contents = file_get_contents($this->path);
        if ($contents === false) {
            Log::warning('Failed to read Composer: '.$this->path);

            return $mappedItems;
        }

        $json = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($json)) {
            Log::warning('Failed to decode Composer: '.$this->path.'. '.json_last_error_msg());

            return $mappedItems;
        }

        if (! array_key_exists('packages', $json)) {
            Log::warning('Malformed composer.lock');

            return $mappedItems;
        }

        $packages = $json['packages'] ?? [];
        $devPackages = $json['packages-dev'] ?? [];

        $this->processPackages($packages, $mappedItems, false);
        $this->processPackages($devPackages, $mappedItems, true);

        return $mappedItems;
    }

    /**
     * Process packages and add them to the mapped items collection
     *
     * @param  array<int, array<string, string>>  $packages
     * @param  Collection<int, Package|Approach>  $mappedItems
     */
    private function processPackages(array $packages, Collection $mappedItems, bool $isDev): void
    {
        foreach ($packages as $package) {
            $packageName = $package['name'] ?? '';
            $version = $package['version'] ?? '';
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
                    Packages::class => new Package($mapped, $packageName, $niceVersion, $isDev),
                    Approaches::class => new Approach($mapped),
                    default => throw new \InvalidArgumentException('Unsupported mapping')
                });
            }
        }
    }
}
