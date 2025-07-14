<?php

namespace Laravel\Using;

use Illuminate\Support\Collection;
use Laravel\Using\Enums\Approaches;
use Laravel\Using\Enums\Packages;
use Laravel\Using\Scanners\Composer;
use Laravel\Using\Scanners\DirectoryStructure;
use Laravel\Using\Scanners\PackageLock;

class Using
{
    /**
     * @var Collection<int, \Laravel\Using\Approach>
     */
    protected Collection $approaches;

    /**
     * @var Collection<int, \Laravel\Using\Package>
     */
    protected Collection $packages;

    public function __construct()
    {
        $this->approaches = collect();
        $this->packages = collect();
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

        /** @var Package|null $package */
        $package = $this->findItem($package);
        if (is_null($package)) {
            return false;
        }

        return version_compare($package->version(), $version, $operator);
    }

    protected function findItem(Packages|Approaches $item): Package|Approach|null
    {
        return match (get_class($item)) {
            Packages::class => $this->packages->first(fn(Package $package) => $package->package()->value === $item->value),
            Approaches::class => $this->approaches->first(fn(Approach $approach) => $approach->approach()->value === $item->value),
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
     * @return Collection<int, \Laravel\Using\Approach>
     */
    public function approaches(): Collection
    {
        return $this->approaches;
    }

    /**
     * @return Collection<int, \Laravel\Using\Package>
     */
    public function packages(): Collection
    {
        return $this->packages;
    }

    /**
     * @return Collection<int, \Laravel\Using\Package>
     */
    public function nonDevPackages()
    {
        return $this->packages->filter(fn(Package $package) => $package->isDev() === false);
    }

    /**
     * @return Collection<int, \Laravel\Using\Package>
     */
    public function devPackages()
    {
        return $this->packages->filter(fn(Package $package) => $package->isDev() === true);
    }

    public static function scan(?string $basePath = null): self
    {
        $using = new self;
        $basePath = ($basePath ?? base_path()) . DIRECTORY_SEPARATOR;

        (new Composer($basePath . 'composer.lock'))
            ->scan()
            ->each(fn($item) => $using->add($item));

        (new PackageLock($basePath . 'package-lock.json'))
            ->scan()
            ->each(fn($item) => $using->add($item));

        (new DirectoryStructure($basePath))
            ->scan()
            ->each(fn($item) => $using->add($item));

        return $using;
    }
}
