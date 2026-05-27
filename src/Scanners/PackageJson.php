<?php

namespace Laravel\Roster\Scanners;

use Laravel\Roster\Enums\PackageSource;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;

class PackageJson extends BasePackageScanner
{
    protected function lockFile(): string
    {
        return 'package.json';
    }

    public function scan(): PackageCollection
    {
        $packages = new PackageCollection;

        foreach ($this->directDependencies() as $name => $meta) {
            $constraint = $meta['constraint'];

            $packages->push(new Package(
                name: $name,
                version: self::normalizeVersion($constraint),
                source: PackageSource::NPM,
                dev: $meta['isDev'],
                direct: true,
                constraint: $constraint,
                path: $this->computePath($name),
            ));
        }

        return $packages;
    }
}
