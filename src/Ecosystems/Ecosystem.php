<?php

declare(strict_types=1);

namespace Laravel\Roster\Ecosystems;

use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use InvalidArgumentException;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use UnexpectedValueException;

abstract class Ecosystem
{
    public function __construct(protected PackageCollection $packages) {}

    /**
     * Returns true if the project uses the given package(s).
     *
     * Single name: `uses('pestphp/pest')` or `uses('pestphp/pest', '^3.0')`.
     * Indexed array: any-of `uses(['pestphp/pest', 'phpunit/phpunit'])`.
     * Assoc array: any-of with constraints `uses(['pestphp/pest' => '^3.0'])`.
     *
     * @param  string|array<int|string, string>  $packages
     *
     * @throws InvalidArgumentException
     */
    public function uses(string|array $packages, ?string $constraint = null): bool
    {
        if (is_string($packages)) {
            return $this->satisfies($packages, $constraint);
        }

        if ($constraint !== null) {
            throw new InvalidArgumentException('The second argument is only valid when the first is a single package name.');
        }

        foreach ($this->normalize($packages) as [$name, $packageConstraint]) {
            if ($this->satisfies($name, $packageConstraint)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if the project uses every listed package.
     *
     * @param  array<int|string, string>  $packages
     *
     * @throws InvalidArgumentException
     */
    public function usesAll(array $packages): bool
    {
        foreach ($this->normalize($packages) as [$name, $packageConstraint]) {
            if (! $this->satisfies($name, $packageConstraint)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the package is present as a direct dependency (declared in the
     * manifest), as opposed to only being pulled in transitively.
     */
    public function usesDirect(string $name): bool
    {
        return $this->package($name)?->isDirect() ?? false;
    }

    public function package(string $name): ?Package
    {
        return $this->packages->first(fn (Package $package): bool => $package->matches($name));
    }

    public function packages(): PackageCollection
    {
        return $this->packages;
    }

    private function satisfies(string $name, ?string $constraint): bool
    {
        if ($constraint !== null) {
            try {
                (new VersionParser)->parseConstraints($constraint);
            } catch (UnexpectedValueException $e) {
                throw new InvalidArgumentException("Invalid semver constraint: {$constraint}", $e->getCode(), $e);
            }
        }

        $package = $this->package($name);
        if (! $package instanceof Package) {
            return false;
        }

        if ($constraint === null) {
            return true;
        }

        $version = $package->version();

        // A version that normalizes to empty (e.g. `dev-main`, `*`) cannot match a constraint.
        if ($version === '') {
            return false;
        }

        return Semver::satisfies($version, $constraint);
    }

    /**
     * @param  array<int|string, string>  $packages
     * @return list<array{0: string, 1: ?string}>
     */
    private function normalize(array $packages): array
    {
        if ($packages === []) {
            return [];
        }

        if (array_is_list($packages)) {
            return array_map(fn (string $name): array => [$name, null], $packages);
        }

        $pairs = [];
        foreach ($packages as $name => $constraint) {
            if (! is_string($name)) {
                throw new InvalidArgumentException('Array must be either all-indexed (list of names) or all-assoc (name => constraint), not mixed.');
            }

            $pairs[] = [$name, $constraint];
        }

        return $pairs;
    }
}
