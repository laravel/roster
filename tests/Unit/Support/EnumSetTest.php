<?php

declare(strict_types=1);

use Laravel\Roster\Enums\Stack;
use Laravel\Roster\Support\EnumSet;

it('checks membership for a single case', function (): void {
    $set = new EnumSet([Stack::LIVEWIRE, Stack::BLADE]);

    expect($set->uses(Stack::LIVEWIRE))->toBeTrue();
    expect($set->uses(Stack::API))->toBeFalse();
});

it('checks any-of membership for an array of cases', function (): void {
    $set = new EnumSet([Stack::LIVEWIRE]);

    expect($set->uses([Stack::INERTIA_REACT, Stack::LIVEWIRE]))->toBeTrue();
    expect($set->uses([Stack::INERTIA_VUE, Stack::API]))->toBeFalse();
});

it('exposes the raw cases and their values', function (): void {
    $set = new EnumSet([Stack::LIVEWIRE, Stack::BLADE]);

    expect($set->all())->toBe([Stack::LIVEWIRE, Stack::BLADE]);
    expect($set->values())->toBe(['livewire', 'blade']);
});

it('is empty by default', function (): void {
    $set = new EnumSet([]);

    expect($set->all())->toBe([]);
    expect($set->uses(Stack::BLADE))->toBeFalse();
});
