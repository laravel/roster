<?php

namespace Laravel\Roster;

use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Laravel\Roster\Detectors\AgentsDetection;
use Laravel\Roster\Ecosystems\JsEcosystem;
use Laravel\Roster\Ecosystems\PhpEcosystem;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Enums\BrowserTestFramework;
use Laravel\Roster\Enums\Frontend;
use Laravel\Roster\Enums\Stack;
use Laravel\Roster\Enums\StarterKit;
use Laravel\Roster\Enums\TestFramework;
use Laravel\Roster\Support\EnumSet;

class RosterManager
{
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

    protected ?Roster $cached = null;

    protected int $ttl = 3600;

    protected bool $useCache = true;

    public function __construct(protected Registry $registry) {}

    /**
     * @param  callable(Registry): void  $callback
     */
    public function extend(callable $callback): self
    {
        $callback($this->registry);
        $this->cached = null;

        return $this;
    }

    public function registry(): Registry
    {
        return $this->registry;
    }

    public function ttl(int $seconds): self
    {
        $this->ttl = $seconds;

        return $this;
    }

    public function withoutCache(): self
    {
        $this->useCache = false;
        $this->cached = null;

        return $this;
    }

    public function scan(?string $basePath = null, bool $detectSystem = true): Roster
    {
        return $this->cached = $this->resolveScan($basePath, $detectSystem);
    }

    public function fresh(): self
    {
        $this->cached = null;

        return $this;
    }

    public function instance(): Roster
    {
        return $this->cached ??= $this->resolveScan(null, true);
    }

    private function resolveScan(?string $basePath, bool $detectSystem): Roster
    {
        if (! $this->useCache) {
            return Roster::scan($basePath, $detectSystem, $this->registry);
        }

        $repo = $this->cacheRepository();
        if (! $repo instanceof Repository) {
            return Roster::scan($basePath, $detectSystem, $this->registry);
        }

        $resolvedBase = $this->resolveBasePath($basePath);
        $key = $this->cacheKey($resolvedBase, $detectSystem);

        $value = rescue(
            fn () => $repo->remember(
                $key,
                $this->ttl,
                fn (): Roster => Roster::scan($resolvedBase, $detectSystem, $this->registry),
            ),
            null,
            report: false,
        );

        return $value instanceof Roster
            ? $value
            : Roster::scan($resolvedBase, $detectSystem, $this->registry);
    }

    private function cacheRepository(): ?CacheRepository
    {
        return rescue(function (): ?CacheRepository {
            $container = Container::getInstance();
            if (! $container->bound('cache')) {
                return null;
            }

            $manager = $container->make('cache');
            if (! is_object($manager) || ! method_exists($manager, 'store')) {
                return null;
            }

            $store = $manager->store();

            return $store instanceof CacheRepository ? $store : null;
        }, null, report: false);
    }

    private function resolveBasePath(?string $basePath): string
    {
        return Roster::normalizeBasePath($basePath);
    }

    private function cacheKey(string $basePath, bool $detectSystem): string
    {
        $parts = [
            'base' => $basePath,
            'system' => $detectSystem,
            'registry' => $this->registry->signature(),
            'locks' => $this->lockfileHash($basePath),
        ];

        return 'roster:'.md5(serialize($parts));
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

    public function testFramework(): ?TestFramework
    {
        return $this->instance()->testFramework();
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

    /** @return EnumSet<StarterKit> */
    public function starterKit(): EnumSet
    {
        return $this->instance()->starterKit();
    }

    public function agents(): AgentsDetection
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
}
