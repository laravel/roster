<?php

declare(strict_types=1);

namespace Laravel\Roster;

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
use Laravel\Roster\Enums\Stack;
use Laravel\Roster\Support\ApproachSet;
use Laravel\Roster\Support\CachesScan;
use Laravel\Roster\Support\EnumSet;

class ProjectManager
{
    use CachesScan;

    protected const LOCKFILES = [
        'composer.lock',
        'composer.json',
        'package-lock.json',
        'pnpm-lock.yaml',
        'yarn.lock',
        'bun.lockb',
        'bun.lock',
        'package.json',
    ];

    protected ?Project $cached = null;

    public function scan(?string $basePath = null): Project
    {
        $resolvedBase = Project::normalizeBasePath($basePath);

        return $this->cached = $this->rememberScan(
            $this->cacheKey($resolvedBase),
            fn (): Project => Project::scan($resolvedBase),
            Project::class,
        );
    }

    public function instance(): Project
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
    public function stack(): EnumSet
    {
        return $this->instance()->stack();
    }

    /** @return EnumSet<BrowserTestFramework> */
    public function browserTestFrameworks(): EnumSet
    {
        return $this->instance()->browserTestFrameworks();
    }

    /** @return EnumSet<Frontend> */
    public function frontend(): EnumSet
    {
        return $this->instance()->frontend();
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

    public function json(): string
    {
        return $this->instance()->json();
    }

    private function cacheKey(string $basePath): string
    {
        return 'roster:project:'.md5(
            $basePath.'|'.$this->lockfileHash($basePath).'|'.$this->markerHash($basePath)
        );
    }

    private function lockfileHash(string $basePath): string
    {
        $hash = hash_init('md5');

        foreach (self::LOCKFILES as $file) {
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
