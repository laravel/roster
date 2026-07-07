<?php

declare(strict_types=1);

use Laravel\Roster\Enums\Stack;
use Laravel\Roster\Support\EnumSet;

it('checks membership for a single case', function (): void {
    $set = new EnumSet([Stack::Livewire, Stack::Blade]);

    expect($set->uses(Stack::Livewire))->toBeTrue();
    expect($set->uses(Stack::Api))->toBeFalse();
});

it('checks any-of membership for an array of cases', function (): void {
    $set = new EnumSet([Stack::Livewire]);

    expect($set->uses([Stack::InertiaReact, Stack::Livewire]))->toBeTrue();
    expect($set->uses([Stack::InertiaVue, Stack::Api]))->toBeFalse();
});

it('checks all-of membership with usesAll', function (): void {
    $set = new EnumSet([Stack::Livewire, Stack::Blade]);

    expect($set->usesAll([Stack::Livewire, Stack::Blade]))->toBeTrue();
    expect($set->usesAll([Stack::Livewire, Stack::Api]))->toBeFalse();
    expect($set->usesAll([]))->toBeTrue();
});

it('exposes the raw cases and their values', function (): void {
    $set = new EnumSet([Stack::Livewire, Stack::Blade]);

    expect($set->all())->toBe([Stack::Livewire, Stack::Blade]);
    expect($set->values())->toBe(['livewire', 'blade']);
});
