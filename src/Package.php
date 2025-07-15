<?php

namespace Laravel\Using;

use Laravel\Using\Enums\Packages;

class Package
{
    public function __construct(protected Packages $package, protected string $version, protected bool $dev = false) {}

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

    public function isDev(): bool
    {
        return $this->dev;
    }
}
