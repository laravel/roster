<?php

namespace Laravel\Roster\Ecosystems;

use InvalidArgumentException;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;

abstract class Ecosystem
{
    public function __construct(protected PackageCollection $packages) {}

    /**
     * @throws InvalidArgumentException
     */
    public function uses(string $name, ?string $version = null, string $operator = '>='): bool
    {
        if ($version !== null) {
            if (! preg_match('/^\d+\.\d+\.\d+/', $version)) {
                throw new InvalidArgumentException('SEMVER required');
            }

            $validOperators = ['<', '<=', '>', '>=', '==', '=', '!=', '<>'];
            if (! in_array($operator, $validOperators, true)) {
                throw new InvalidArgumentException('Invalid operator');
            }
        }

        $package = $this->package($name);
        if (! $package instanceof Package) {
            return false;
        }

        if ($version === null) {
            return true;
        }

        return version_compare($package->version(), $version, $operator);
    }

    public function package(string $name): ?Package
    {
        return $this->packages->first(fn (Package $package): bool => $package->matches($name));
    }

    public function packages(): PackageCollection
    {
        return $this->packages;
    }
}
