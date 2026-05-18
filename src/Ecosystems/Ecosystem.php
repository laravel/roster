<?php

namespace Laravel\Roster\Ecosystems;

use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;

abstract class Ecosystem
{
    public function __construct(protected PackageCollection $packages) {}

    /**
     * @throws \InvalidArgumentException
     */
    public function uses(string $name, ?string $version = null, string $operator = '>='): bool
    {
        if ($version !== null) {
            if (! preg_match('/^[0-9]+\.[0-9]+\.[0-9]+/', $version)) {
                throw new \InvalidArgumentException('SEMVER required');
            }

            $validOperators = ['<', '<=', '>', '>=', '==', '=', '!=', '<>'];
            if (! in_array($operator, $validOperators, true)) {
                throw new \InvalidArgumentException('Invalid operator');
            }
        }

        $package = $this->package($name);
        if ($package === null) {
            return false;
        }

        if ($version === null) {
            return true;
        }

        return version_compare($package->version(), $version, $operator);
    }

    public function package(string $name): ?Package
    {
        return $this->packages->first(fn (Package $package) => $package->matches($name));
    }

    public function packages(): PackageCollection
    {
        return $this->packages;
    }
}
