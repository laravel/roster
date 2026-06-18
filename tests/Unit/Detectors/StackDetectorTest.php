<?php

declare(strict_types=1);

use Laravel\Roster\Detectors\StackDetector;
use Laravel\Roster\Enums\Stack;

it('detects inertia stack variants from JS adapter', function (): void {
    $stacks = StackDetector::detect(
        phpEcosystem(['inertiajs/inertia-laravel']),
        jsEcosystem(['@inertiajs/react']),
    );

    expect($stacks)->toContain(Stack::INERTIA_REACT);
});

it('detects livewire stack', function (): void {
    $stacks = StackDetector::detect(phpEcosystem(['livewire/livewire']), jsEcosystem([]));
    expect($stacks)->toContain(Stack::LIVEWIRE);
});

it('detects api stack when sanctum present and no view layer', function (): void {
    $stacks = StackDetector::detect(phpEcosystem(['laravel/sanctum']), jsEcosystem([]));
    expect($stacks)->toContain(Stack::API);
});

it('falls back to blade by default', function (): void {
    $stacks = StackDetector::detect(phpEcosystem(['laravel/framework']), jsEcosystem([]));
    expect($stacks)->toContain(Stack::BLADE);
});
