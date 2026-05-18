<?php

use Laravel\Roster\Detectors\StackDetector;
use Laravel\Roster\Enums\Stack;

it('detects inertia stack variants from JS adapter', function (): void {
    $stack = (new StackDetector)->detect(
        phpEcosystem(['inertiajs/inertia-laravel']),
        jsEcosystem(['@inertiajs/react']),
    );

    expect($stack->uses(Stack::INERTIA_REACT))->toBeTrue();
});

it('detects livewire stack', function (): void {
    $stack = (new StackDetector)->detect(phpEcosystem(['livewire/livewire']), jsEcosystem([]));
    expect($stack->uses(Stack::LIVEWIRE))->toBeTrue();
});

it('detects api stack when sanctum present and no view layer', function (): void {
    $stack = (new StackDetector)->detect(phpEcosystem(['laravel/sanctum']), jsEcosystem([]));
    expect($stack->uses(Stack::API))->toBeTrue();
});

it('falls back to blade by default', function (): void {
    $stack = (new StackDetector)->detect(phpEcosystem(['laravel/framework']), jsEcosystem([]));
    expect($stack->uses(Stack::BLADE))->toBeTrue();
});

it('supports any-of via array argument', function (): void {
    $stack = (new StackDetector)->detect(
        phpEcosystem(['livewire/livewire']),
        jsEcosystem(['@inertiajs/react']),
    );

    expect($stack->uses([Stack::INERTIA_REACT, Stack::LIVEWIRE]))->toBeTrue();
    expect($stack->uses([Stack::INERTIA_VUE, Stack::API]))->toBeFalse();
});
