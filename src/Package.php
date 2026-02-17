<?php

namespace Laravel\Roster;

use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Enums\PackageSource;

class Package
{
    protected bool $direct = false;

    protected string $constraint = '';

    protected ?PackageSource $source = null;

    public function __construct(
        protected Packages $package,
        protected string $packageName,
        protected string $version,
        protected bool $dev = false,
        protected ?string $path = null
    ) {
        //
    }

    public function setDev(bool $dev = true): self
    {
        $this->dev = $dev;

        return $this;
    }

    public function setDirect(bool $direct = true): self
    {
        $this->direct = $direct;

        return $this;
    }

    public function setConstraint(string $constraint = ''): self
    {
        $this->constraint = $constraint;

        return $this;
    }

    public function setSource(PackageSource $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function name(): string
    {
        return $this->package->name;
    }

    public function package(): Packages
    {
        return $this->package;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function direct(): bool
    {
        return $this->direct;
    }

    public function indirect(): bool
    {
        return ! $this->direct;
    }

    public function constraint(): string
    {
        return $this->constraint;
    }

    public function majorVersion(): string
    {
        return explode('.', $this->version)[0];
    }

    public function isDev(): bool
    {
        return $this->dev;
    }

    public function source(): ?PackageSource
    {
        return $this->source;
    }

    public function path(): ?string
    {
        return $this->path;
    }

    public function rawName(): string
    {
        return $this->packageName;
    }
}
