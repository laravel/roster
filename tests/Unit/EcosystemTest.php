<?php

use Laravel\Roster\Ecosystems\PhpEcosystem;
use Laravel\Roster\PackageCollection;

it('finds a package by alias or raw name', function () {
    $php = phpEcosystem([
        ['name' => 'pestphp/pest', 'alias' => 'pest', 'dev' => true],
    ]);

    expect($php->uses('pest'))->toBeTrue();
    expect($php->uses('pestphp/pest'))->toBeTrue();
    expect($php->uses('phpunit'))->toBeFalse();
});

it('compares versions through uses', function () {
    $php = phpEcosystem([
        ['name' => 'laravel/framework', 'version' => '11.44.2', 'alias' => 'framework'],
    ]);

    expect($php->uses('framework', '11.0.0'))->toBeTrue();
    expect($php->uses('framework', '12.0.0'))->toBeFalse();
    expect($php->uses('framework', '11.0.0', '<'))->toBeFalse();
    expect($php->uses('unknown', '1.0.0'))->toBeFalse();
});

it('throws on invalid version or operator', function () {
    $php = new PhpEcosystem(new PackageCollection);

    expect(fn () => $php->uses('foo', '1.x'))->toThrow(InvalidArgumentException::class);
    expect(fn () => $php->uses('foo', '1.0.0', '##bad##'))->toThrow(InvalidArgumentException::class);
});

it('exposes dev / production filters', function () {
    $php = phpEcosystem([
        ['name' => 'laravel/framework', 'alias' => 'framework'],
        ['name' => 'pestphp/pest', 'alias' => 'pest', 'dev' => true],
    ]);

    expect($php->packages()->production()->count())->toBe(1);
    expect($php->packages()->dev()->count())->toBe(1);
});
