<?php

declare(strict_types=1);

namespace Laravel\Roster\Scanners;

use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;

class PackageJson extends JsPackageScanner
{
    public function scan(): PackageCollection
    {
        $packages = new PackageCollection;

        foreach ($this->directDependencies() as $name => $meta) {
            $constraint = $meta['constraint'];

            $packages->push(new Package(
                name: $name,
                version: self::normalizeVersion($constraint),
                source: $this->source(),
                dev: $meta['isDev'],
                direct: true,
                constraint: $constraint,
                path: $this->computePath($name),
            ));
        }

        return $packages;
    }
}
