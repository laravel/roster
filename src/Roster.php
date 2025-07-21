<?php

namespace Laravel\Roster;

use Illuminate\Support\Collection;
use Laravel\Roster\Enums\Approaches;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Scanners\Composer;
use Laravel\Roster\Scanners\DirectoryStructure;
use Laravel\Roster\Scanners\PackageLock;

class Roster
{
    /**
     * @var Collection<int, \Laravel\Roster\Approach>
     */
    protected Collection $approaches;

    protected PackageCollection $packages;

    public function __construct()
    {
        $this->approaches = collect();
        $this->packages = new PackageCollection;
    }

    public function add(Package|Approach $item): self
    {
        $method = 'add' . ucfirst(strtolower(class_basename($item)));

        return $this->$method($item);
    }

    public function uses(Packages|Approaches $item): bool
    {
        return $this->findItem($item) !== null;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function usesVersion(Packages $package, string $version, string $operator = '='): bool
    {
        if (!preg_match('/[0-9]{1,}\.[0-9]{1,}\.[0-9]{1,}/', $version)) {
            throw new \InvalidArgumentException('SEMVER required');
        }

        $validOperators = ['<', '<=', '>', '>=', '==', '=', '!=', '<>'];
        if (!in_array($operator, $validOperators)) {
            throw new \InvalidArgumentException('Invalid operator');
        }

        $package = $this->findItem($package);
        if (is_null($package)) {
            return false;
        }

        /** @var \Laravel\Roster\Package $package */
        return version_compare($package->version(), $version, $operator);
    }

    protected function findItem(Packages|Approaches $item): Package|Approach|null
    {
        return match (get_class($item)) {
            Packages::class => $this->package($item),
            Approaches::class => $this->approach($item),
            default => null,
        };
    }

    protected function addPackage(Package $package): self
    {
        $this->packages->push($package);

        return $this;
    }

    protected function addApproach(Approach $approach): self
    {
        $this->approaches->push($approach);

        return $this;
    }

    /**
     * @return Collection<int, \Laravel\Roster\Approach>
     */
    public function approaches(): Collection
    {
        return $this->approaches;
    }

    public function packages(): PackageCollection
    {
        return $this->packages;
    }

    public function package(Packages $package): ?Package
    {
        return $this->packages->first(fn(Package $item) => $item->package()->value === $package->value);
    }

    public function approach(Approaches $approach): ?Approach
    {
        return $this->approaches->first(fn(Approach $item) => $item->approach()->value === $approach->value);
    }

    public static function scan(?string $basePath = null): self
    {
        $roster = new self;
        $basePath = ($basePath ?? base_path()) . DIRECTORY_SEPARATOR;

        (new Composer($basePath . 'composer.lock'))
            ->scan()
            ->each(fn($item) => $roster->add($item));

        (new PackageLock($basePath . 'package-lock.json'))
            ->scan()
            ->each(fn($item) => $roster->add($item));

        (new DirectoryStructure($basePath))
            ->scan()
            ->each(fn($item) => $roster->add($item));

        return $roster;
    }
}
