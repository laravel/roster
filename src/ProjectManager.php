<?php

declare(strict_types=1);

namespace Laravel\Roster;

use Laravel\Roster\Ecosystems\JsEcosystem;
use Laravel\Roster\Ecosystems\PhpEcosystem;
use Laravel\Roster\Enums\Agent;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Enums\BrowserTestFramework;
use Laravel\Roster\Enums\Frontend;
use Laravel\Roster\Enums\Stack;
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

    public function php(): PhpEcosystem
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

    /** @return EnumSet<Approach> */
    public function approach(): EnumSet
    {
        return $this->instance()->approach();
    }

    public function json(): string
    {
        return $this->instance()->json();
    }

    protected function resetCachedInstance(): void
    {
        $this->cached = null;
    }

    private function cacheKey(string $basePath): string
    {
        return 'roster:project:'.md5($basePath.'|'.$this->lockfileHash($basePath));
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
}
