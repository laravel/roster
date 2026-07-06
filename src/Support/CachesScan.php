<?php

declare(strict_types=1);

namespace Laravel\Roster\Support;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

trait CachesScan
{
    protected int $ttl = 3600;

    /**
     * @template TValue of object
     *
     * @param  Closure(): TValue  $scan
     * @param  class-string<TValue>  $expected
     * @return TValue
     */
    protected function rememberScan(string $key, Closure $scan, string $expected): object
    {
        $repo = $this->cacheRepository();
        if (! $repo instanceof CacheRepository) {
            return $scan();
        }

        $value = rescue(
            fn () => $repo->remember($key, $this->ttl, $scan),
            null,
            report: false,
        );

        return $value instanceof $expected ? $value : $scan();
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
}
