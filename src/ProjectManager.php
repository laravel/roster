<?php

declare(strict_types=1);

namespace Laravel\Roster;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository;
use Laravel\Roster\Detectors\AgentsDetector;
use Laravel\Roster\Detectors\BrowserTestFrameworkDetector;
use Laravel\Roster\Detectors\EditorsDetector;
use Laravel\Roster\Detectors\MarkerDetector;
use Laravel\Roster\Ecosystems\Ecosystem;
use Laravel\Roster\Ecosystems\JsEcosystem;
use Laravel\Roster\Enums\Agent;
use Laravel\Roster\Enums\BrowserTestFramework;
use Laravel\Roster\Enums\Editor;
use Laravel\Roster\Enums\Frontend;
use Laravel\Roster\Enums\JsPackageManager;
use Laravel\Roster\Enums\Stack;
use Laravel\Roster\Support\ApproachSet;
use Laravel\Roster\Support\EnumSet;
use Throwable;

class ProjectManager
{
    protected const CACHE_TTL = 3600;

    protected ?ProjectScan $cached = null;

    public function scan(?string $basePath = null): ProjectScan
    {
        $resolvedBase = ProjectScan::normalizeBasePath($basePath);

        $project = $this->rememberScan(
            $this->cacheKey($resolvedBase),
            fn (): ProjectScan => ProjectScan::scan($resolvedBase),
        );

        return $basePath === null ? ($this->cached = $project) : $project;
    }

    public function fresh(?string $basePath = null): ProjectScan
    {
        $project = ProjectScan::scan(ProjectScan::normalizeBasePath($basePath));

        return $basePath === null ? ($this->cached = $project) : $project;
    }

    public function instance(): ProjectScan
    {
        return $this->cached ??= $this->scan();
    }

    public function php(): Ecosystem
    {
        return $this->instance()->php();
    }

    public function js(): JsEcosystem
    {
        return $this->instance()->js();
    }

    /** @return EnumSet<Stack> */
    public function stacks(): EnumSet
    {
        return $this->instance()->stacks();
    }

    /** @return EnumSet<BrowserTestFramework> */
    public function browserTestFrameworks(): EnumSet
    {
        return $this->instance()->browserTestFrameworks();
    }

    /** @return EnumSet<Frontend> */
    public function frontends(): EnumSet
    {
        return $this->instance()->frontends();
    }

    /** @return EnumSet<Agent> */
    public function agents(): EnumSet
    {
        return $this->instance()->agents();
    }

    /** @return EnumSet<Editor> */
    public function editors(): EnumSet
    {
        return $this->instance()->editors();
    }

    public function approaches(): ApproachSet
    {
        return $this->instance()->approaches();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->instance()->toArray();
    }

    public function json(): string
    {
        return $this->instance()->json();
    }

    /**
     * @param  Closure(): ProjectScan  $scan
     */
    private function rememberScan(string $key, Closure $scan): ProjectScan
    {
        $store = $this->cacheStore();

        try {
            $cached = $store?->get($key);

            if ($cached instanceof ProjectScan) {
                return $cached;
            }
        } catch (Throwable) {
            //
        }

        $project = $scan();

        try {
            $store?->put($key, $project, self::CACHE_TTL);
        } catch (Throwable) {
            //
        }

        return $project;
    }

    private function cacheStore(): ?Repository
    {
        try {
            $manager = Container::getInstance()->make('cache');

            return $manager instanceof CacheFactory ? $manager->store() : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function cacheKey(string $basePath): string
    {
        return 'roster:project:v3:'.md5(
            $basePath.'|'.$this->lockfileHash($basePath).'|'.$this->markerHash($basePath)
        );
    }

    /**
     * @return list<string>
     */
    private function lockfiles(): array
    {
        $jsLockfiles = array_merge(...array_map(
            fn (JsPackageManager $manager): array => $manager->lockFiles(),
            JsPackageManager::cases(),
        ));

        return ['composer.lock', 'composer.json', ...$jsLockfiles, 'package.json'];
    }

    private function lockfileHash(string $basePath): string
    {
        $hash = hash_init('md5');

        foreach ($this->lockfiles() as $file) {
            $path = $basePath.$file;
            $fileHash = is_file($path) ? @md5_file($path) : null;
            hash_update($hash, $file.':'.($fileHash ?: '0').'|');
        }

        return hash_final($hash);
    }

    private function markerHash(string $basePath): string
    {
        $markers = [
            ...AgentsDetector::markerPaths(),
            ...EditorsDetector::markerPaths(),
            ...BrowserTestFrameworkDetector::markerPaths(),
        ];

        $markers = array_values(array_unique($markers));
        sort($markers);

        $hash = hash_init('md5');

        foreach ($markers as $marker) {
            hash_update($hash, $marker.':'.(MarkerDetector::markerMatches($basePath, $marker) ? '1' : '0').'|');
        }

        return hash_final($hash);
    }
}
