<?php

declare(strict_types=1);

use Laravel\Roster\Ecosystems\PhpEcosystem;
use Laravel\Roster\PackageCollection;

it('finds a package by raw name', function (): void {
    $php = phpEcosystem([
        ['name' => 'pestphp/pest', 'dev' => true],
    ]);

    expect($php->uses('pestphp/pest'))->toBeTrue();
    expect($php->uses('phpunit/phpunit'))->toBeFalse();
});

it('compares versions through uses with semver constraints', function (): void {
    $php = phpEcosystem([
        ['name' => 'laravel/framework', 'version' => '11.44.2'],
    ]);

    expect($php->uses('laravel/framework', '>=11.0.0'))->toBeTrue();
    expect($php->uses('laravel/framework', '>=12.0.0'))->toBeFalse();
    expect($php->uses('laravel/framework', '<11.0.0'))->toBeFalse();
    expect($php->uses('laravel/framework', '^11.0'))->toBeTrue();
    expect($php->uses('laravel/framework', '~11.44.0'))->toBeTrue();
    expect($php->uses('laravel/framework', '^12.0'))->toBeFalse();
    expect($php->uses('laravel/framework', '>=11 <12'))->toBeTrue();
    expect($php->uses('laravel/framework', '11.44.2'))->toBeTrue();
    expect($php->uses('laravel/framework', '11.0.0'))->toBeFalse();
    expect($php->uses('unknown', '^1.0'))->toBeFalse();
});

it('throws on invalid semver constraint', function (): void {
    $php = new PhpEcosystem(new PackageCollection);

    expect(fn (): bool => $php->uses('foo', 'not-a-constraint'))->toThrow(InvalidArgumentException::class);
});

it('does not satisfy a constraint when the package version is empty', function (): void {
    $php = phpEcosystem([
        ['name' => 'vendor/dev-pkg', 'version' => ''],
    ]);

    expect($php->uses('vendor/dev-pkg'))->toBeTrue()
        ->and($php->uses('vendor/dev-pkg', '^1.0'))->toBeFalse()
        ->and($php->usesAll(['vendor/dev-pkg' => '^1.0']))->toBeFalse();
});

it('uses with array of names checks any-of', function (): void {
    $php = phpEcosystem([
        ['name' => 'pestphp/pest'],
    ]);

    expect($php->uses(['pestphp/pest', 'phpunit/phpunit']))->toBeTrue();
    expect($php->uses(['phpunit/phpunit', 'mockery/mockery']))->toBeFalse();
    expect($php->uses([]))->toBeFalse();
});

it('uses with assoc array applies per-package constraints', function (): void {
    $php = phpEcosystem([
        ['name' => 'pestphp/pest', 'version' => '3.8.1'],
        ['name' => 'laravel/framework', 'version' => '11.44.2'],
    ]);

    expect($php->uses(['pestphp/pest' => '^3.0', 'laravel/framework' => '^11.0']))->toBeTrue();
    expect($php->uses(['pestphp/pest' => '^4.0', 'laravel/framework' => '^11.0']))->toBeTrue();
    expect($php->uses(['pestphp/pest' => '^4.0', 'laravel/framework' => '^12.0']))->toBeFalse();
});

it('uses rejects mixed indexed/assoc arrays', function (): void {
    $php = phpEcosystem([['name' => 'pestphp/pest']]);

    expect(fn (): bool => $php->uses(['pestphp/pest', 'laravel/framework' => '^11.0']))
        ->toThrow(InvalidArgumentException::class);
});

it('uses rejects array + constraint argument combination', function (): void {
    $php = phpEcosystem([['name' => 'pestphp/pest']]);

    expect(fn (): bool => $php->uses(['pestphp/pest'], '^3.0'))
        ->toThrow(InvalidArgumentException::class);
});

it('usesAll requires every listed package', function (): void {
    $php = phpEcosystem([
        ['name' => 'pestphp/pest', 'version' => '3.8.1'],
        ['name' => 'laravel/framework', 'version' => '11.44.2'],
    ]);

    expect($php->usesAll(['pestphp/pest', 'laravel/framework']))->toBeTrue();
    expect($php->usesAll(['pestphp/pest', 'phpunit/phpunit']))->toBeFalse();
    expect($php->usesAll([]))->toBeTrue();
});

it('usesAll applies per-package constraints', function (): void {
    $php = phpEcosystem([
        ['name' => 'pestphp/pest', 'version' => '3.8.1'],
        ['name' => 'laravel/framework', 'version' => '11.44.2'],
    ]);

    expect($php->usesAll(['pestphp/pest' => '^3.0', 'laravel/framework' => '^11.0']))->toBeTrue();
    expect($php->usesAll(['pestphp/pest' => '^4.0', 'laravel/framework' => '^11.0']))->toBeFalse();
});

it('exposes dev / production filters', function (): void {
    $php = phpEcosystem([
        ['name' => 'laravel/framework'],
        ['name' => 'pestphp/pest', 'dev' => true],
    ]);

    expect($php->packages()->production()->count())->toBe(1);
    expect($php->packages()->dev()->count())->toBe(1);
});
