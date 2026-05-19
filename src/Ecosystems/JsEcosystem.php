<?php

declare(strict_types=1);

namespace Laravel\Roster\Ecosystems;

use Laravel\Roster\Enums\JsPackageManager;
use Laravel\Roster\PackageCollection;

class JsEcosystem extends Ecosystem
{
    public function __construct(
        PackageCollection $packages,
        protected ?JsPackageManager $packageManager,
    ) {
        parent::__construct($packages);
    }

    public function packageManager(): ?JsPackageManager
    {
        return $this->packageManager;
    }
}
