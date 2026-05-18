<?php

use Laravel\Roster\Enums\PackageSource;
use Laravel\Roster\Registry;

it('auto-aliases laravel/* by stripping vendor', function (): void {
    $registry = new Registry;

    expect($registry->aliasFor(PackageSource::COMPOSER, 'laravel/pint'))->toBe('pint');
    expect($registry->aliasFor(PackageSource::COMPOSER, 'laravel/framework'))->toBe('framework');
});

it('auto-aliases inertiajs/* with inertia- prefix when missing', function (): void {
    $registry = new Registry;

    expect($registry->aliasFor(PackageSource::COMPOSER, 'inertiajs/inertia-laravel'))->toBe('inertia-laravel');
    expect($registry->aliasFor(PackageSource::NPM, '@inertiajs/react'))->toBe('inertia-react');
    expect($registry->aliasFor(PackageSource::NPM, '@inertiajs/vue3'))->toBe('inertia-vue3');
});

it('auto-aliases pestphp/* with pest- prefix when missing', function (): void {
    $registry = new Registry;

    expect($registry->aliasFor(PackageSource::COMPOSER, 'pestphp/pest'))->toBe('pest');
    expect($registry->aliasFor(PackageSource::COMPOSER, 'pestphp/pest-plugin-browser'))->toBe('pest-plugin-browser');
    expect($registry->aliasFor(PackageSource::COMPOSER, 'pestphp/plugin-foo'))->toBe('pest-plugin-foo');
});

it('returns null for unknown vendors', function (): void {
    $registry = new Registry;

    expect($registry->aliasFor(PackageSource::COMPOSER, 'spatie/laravel-permission'))->toBeNull();
    expect($registry->aliasFor(PackageSource::NPM, '@vue/compiler-sfc'))->toBeNull();
    expect($registry->aliasFor(PackageSource::NPM, 'react'))->toBeNull();
});

it('honors explicit aliases over auto-alias', function (): void {
    $registry = (new Registry)
        ->php('spatie/laravel-permission', 'permission')
        ->js('@tanstack/react-query', 'react-query');

    expect($registry->aliasFor(PackageSource::COMPOSER, 'spatie/laravel-permission'))->toBe('permission');
    expect($registry->aliasFor(PackageSource::NPM, '@tanstack/react-query'))->toBe('react-query');
});
