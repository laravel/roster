<?php

declare(strict_types=1);

namespace Laravel\Roster\Ecosystems;

use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use UnexpectedValueException;

class Ecosystem
{
    /** @var array<string, Package> */
    protected array $byName = [];

    public function __construct(protected PackageCollection $packages)
    {
        foreach ($packages as $package) {
            $this->byName[$package->name()] ??= $package;
        }
    }

    /**
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
     * @param  string|array<int, string>  $packages
     */
    public function usesDirect(string|array $packages): bool
    {
        foreach (Arr::wrap($packages) as $name) {
            if ($this->package($name)?->isDirect() ?? false) {
                return true;
            }
        }

        return false;
    }

    public function package(string $name): ?Package
    {
        return $this->byName[$name] ?? null;
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

        if ($version === '') {
            return false;
        }

        try {
            return Semver::satisfies($version, $constraint);
        } catch (UnexpectedValueException) {
            return false;
        }
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
