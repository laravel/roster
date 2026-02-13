<?php

use Laravel\Roster\Approach;
use Laravel\Roster\Enums\Approaches;
use Laravel\Roster\Enums\NodePackageManager;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Enums\PackageSource;
use Laravel\Roster\Package;
use Laravel\Roster\Roster;

it('can add packages and approaches to roster', function () {
    $package = new Package(Packages::PEST, 'pestphp/pest', '1.0.1');
    $approach = new Approach(Approaches::DDD);
    $roster = new Roster;
    $roster->add($package);
    $roster->add($approach);

    expect($roster)->packages()->toArray()->toBe([$package]);
    expect($roster)->approaches()->toArray()->toBe([$approach]);
});

it('knows if a package is in use', function () {
    $package = new Package(Packages::PEST, 'pestphp/pest', '1.0.1');
    $roster = (new Roster)->add($package);

    expect($roster->uses(Packages::PEST))->toBeTrue();
    expect($roster->uses(Packages::NOVA))->toBeFalse();
});

it('knows if a specific version of a package is in use', function () {
    $usedPackage = new Package(Packages::PEST, 'pestphp/pest', '1.0.1');
    $roster = (new Roster)->add($usedPackage);

    expect($roster->uses(Packages::PEST))->toBeTrue();
    expect($roster->usesVersion(Packages::NOVA, '1.0.1'))->toBeFalse();
    expect($roster->usesVersion(Packages::PEST, '1.0.1'))->toBeTrue();

    expect($roster->usesVersion(Packages::PEST, '1.0.1', '='))->toBeTrue();
    expect($roster->usesVersion(Packages::PEST, '1.0.1', '=='))->toBeTrue();
    expect($roster->usesVersion(Packages::PEST, '1.0.1', '>='))->toBeTrue();
    expect($roster->usesVersion(Packages::PEST, '1.0.1', '<='))->toBeTrue();
    expect($roster->usesVersion(Packages::PEST, '1.0.2', '<='))->toBeTrue();
    expect($roster->usesVersion(Packages::PEST, '1.0.2', '!='))->toBeTrue();
    expect($roster->usesVersion(Packages::PEST, '1.0.2', '<>'))->toBeTrue();

    expect($roster->usesVersion(Packages::PEST, '1.0.2', '>='))->toBeFalse();
    expect($roster->usesVersion(Packages::PEST, '1.0.0', '<='))->toBeFalse();
});

it('throws an exception with an invalid version when checking version usage', function () {
    (new Roster)->usesVersion(Packages::PEST, '1.0.x', '##INVALID##');
})->throws(InvalidArgumentException::class);

it('throws an exception with an invalid operator when checking version usage', function () {
    (new Roster)->usesVersion(Packages::PEST, '1.0.0', '##INVALID##');
})->throws(InvalidArgumentException::class);

it('knows if an approach is in use', function () {
    $approach = new Approach(Approaches::DDD);
    $roster = (new Roster)->add($approach);

    expect($roster->uses(Approaches::DDD))->toBeTrue();
    expect($roster->uses(Approaches::ACTION))->toBeFalse();
    expect($roster->uses(Approaches::MODULAR))->toBeFalse();
});

it('can return dev packages', function () {
    $devPackage = new Package(Packages::PEST, 'pestphp/pest', '1.0.1', true);
    $package = new Package(Packages::LARAVEL, 'laravel/framework', '2.0.0');
    $roster = (new Roster)->add($devPackage)->add($package);

    expect($roster->uses(Packages::LARAVEL))->toBeTrue();
    expect($roster->uses(Packages::PEST))->toBeTrue();

    expect($roster->packages()->dev()->toArray())->toBe([$devPackage]);
});

it('can return a specific package', function () {
    $package = new Package(Packages::PEST, 'pestphp/pest', '1.0.1');
    $roster = (new Roster)->add($package);

    expect($roster->package(Packages::PEST))->toBe($package);
    expect($roster->package(Packages::NOVA))->toBeNull();
});

it('can return raw package name', function () {
    $package = new Package(Packages::PEST, 'pestphp/pest', '1.0.1');

    expect($package->rawName())->toBe('pestphp/pest');
    expect($package->name())->toBe('PEST');
});

it('has null source and path by default', function () {
    $package = new Package(Packages::PEST, 'pestphp/pest', '1.0.1');

    expect($package->source())->toBeNull();
    expect($package->path())->toBeNull();
});

it('can set and get source and path', function () {
    $package = new Package(Packages::PEST, 'pestphp/pest', '1.0.1');
    $package->setSource(PackageSource::COMPOSER)->setPath('/some/path/vendor/pestphp/pest');

    expect($package->source())->toBe(PackageSource::COMPOSER);
    expect($package->path())->toBe('/some/path/vendor/pestphp/pest');
});

describe('node package manager detection', function () {
    beforeEach(function () {
        $this->path = __DIR__.'/../fixtures/fog/';
        $this->lockFiles = [
            'package-lock.json' => $this->path.'package-lock.json',
            'pnpm-lock.yaml' => $this->path.'pnpm-lock.yaml',
            'yarn.lock' => $this->path.'yarn.lock',
            'bun.lock' => $this->path.'bun.lock',
        ];
    });

    afterEach(function () {
        foreach ($this->lockFiles as $file) {
            $tempPath = $file.'.bac';
            if (file_exists($tempPath)) {
                rename($tempPath, $file);
            }
        }
    });

    it('can detect :manager as node package manager', function (string $requiredFile, NodePackageManager $expected) {
        foreach ($this->lockFiles as $fileName => $filePath) {
            if ($fileName !== $requiredFile && file_exists($filePath)) {
                rename($filePath, $filePath.'.bac');
            }
        }

        $roster = Roster::scan($this->path);

        expect($roster->nodePackageManager())->toBe($expected);
    })->with([
        'npm' => ['package-lock.json', NodePackageManager::NPM],
        'pnpm' => ['pnpm-lock.yaml', NodePackageManager::PNPM],
        'yarn' => ['yarn.lock', NodePackageManager::YARN],
        'bun' => ['bun.lock', NodePackageManager::BUN],
    ]);

    it('defaults to npm when no lock files exist', function () {
        $path = __DIR__.'/../fixtures/phpunit/';
        $roster = Roster::scan($path);

        expect($roster->nodePackageManager())->toBe(NodePackageManager::NPM);
    });
});
