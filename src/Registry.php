<?php

namespace Laravel\Roster;

use Laravel\Roster\Enums\PackageSource;

class Registry
{
    /** @var array<string, string> raw name => alias */
    protected array $phpAliases = [];

    /** @var array<string, string> raw name => alias */
    protected array $jsAliases = [];

    public function php(string $name, string $alias): self
    {
        $this->phpAliases[$name] = $alias;

        return $this;
    }

    public function js(string $name, string $alias): self
    {
        $this->jsAliases[$name] = $alias;

        return $this;
    }

    public function signature(): string
    {
        ksort($this->phpAliases);
        ksort($this->jsAliases);

        return md5(serialize([$this->phpAliases, $this->jsAliases]));
    }

    public function aliasFor(PackageSource $source, string $rawName): ?string
    {
        $explicit = $source === PackageSource::COMPOSER
            ? ($this->phpAliases[$rawName] ?? null)
            : ($this->jsAliases[$rawName] ?? null);

        if ($explicit !== null) {
            return $explicit;
        }

        return $source === PackageSource::COMPOSER
            ? $this->autoAliasComposer($rawName)
            : $this->autoAliasNpm($rawName);
    }

    protected function autoAliasComposer(string $name): ?string
    {
        if (! str_contains($name, '/')) {
            return null;
        }

        [$vendor, $package] = explode('/', $name, 2);

        return match ($vendor) {
            'laravel' => $package,
            'inertiajs' => str_starts_with($package, 'inertia') ? $package : 'inertia-'.$package,
            'pestphp' => str_starts_with($package, 'pest') ? $package : 'pest-'.$package,
            default => null,
        };
    }

    protected function autoAliasNpm(string $name): ?string
    {
        if (! str_starts_with($name, '@')) {
            return null;
        }

        if (! str_contains($name, '/')) {
            return null;
        }

        [$scope, $package] = explode('/', substr($name, 1), 2);

        return match ($scope) {
            'inertiajs' => str_starts_with($package, 'inertia') ? $package : 'inertia-'.$package,
            default => null,
        };
    }
}
