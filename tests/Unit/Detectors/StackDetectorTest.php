<?php

declare(strict_types=1);

use Laravel\Roster\Detectors\StackDetector;
use Laravel\Roster\Enums\Stack;

it('detects inertia stack variants from JS adapter', function (): void {
    $stacks = StackDetector::detect(
        phpEcosystem(['inertiajs/inertia-laravel']),
        jsEcosystem(['@inertiajs/react']),
    );

    expect($stacks)->toContain(Stack::InertiaReact);
});

it('detects inertia vue including the legacy adapter', function (): void {
    expect(StackDetector::detect(phpEcosystem([]), jsEcosystem(['@inertiajs/vue3'])))
        ->toContain(Stack::InertiaVue);

    expect(StackDetector::detect(phpEcosystem([]), jsEcosystem(['@inertiajs/vue'])))
        ->toContain(Stack::InertiaVue);
});

it('detects inertia svelte', function (): void {
    expect(StackDetector::detect(phpEcosystem([]), jsEcosystem(['@inertiajs/svelte'])))
        ->toContain(Stack::InertiaSvelte);
});

it('detects livewire stack', function (): void {
    $stacks = StackDetector::detect(phpEcosystem(['livewire/livewire']), jsEcosystem([]));
    expect($stacks)->toContain(Stack::Livewire);
});

it('detects livewire stack for volt-only projects', function (): void {
    $stacks = StackDetector::detect(phpEcosystem(['livewire/volt', 'laravel/framework']), jsEcosystem([]));

    expect($stacks)->toContain(Stack::Livewire);
    expect($stacks)->not->toContain(Stack::Blade);
});

it('detects api stack when sanctum present and no view layer', function (): void {
    $stacks = StackDetector::detect(phpEcosystem(['laravel/sanctum']), jsEcosystem([]));
    expect($stacks)->toContain(Stack::Api);
});

it('detects api stack when passport present and no view layer', function (): void {
    $stacks = StackDetector::detect(phpEcosystem(['laravel/passport']), jsEcosystem([]));
    expect($stacks)->toContain(Stack::Api);
});

it('suppresses api stack when a view layer is present', function (): void {
    expect(StackDetector::detect(phpEcosystem(['laravel/sanctum', 'livewire/livewire']), jsEcosystem([])))
        ->not->toContain(Stack::Api);

    expect(StackDetector::detect(phpEcosystem(['laravel/sanctum', 'laravel/folio']), jsEcosystem([])))
        ->not->toContain(Stack::Api);
});

it('falls back to blade by default', function (): void {
    $stacks = StackDetector::detect(phpEcosystem(['laravel/framework']), jsEcosystem([]));
    expect($stacks)->toContain(Stack::Blade);
});

it('does not add blade when another stack is detected', function (): void {
    $stacks = StackDetector::detect(phpEcosystem(['livewire/livewire', 'laravel/framework']), jsEcosystem([]));
    expect($stacks)->not->toContain(Stack::Blade);
});

it('detects no stack for non-laravel projects', function (): void {
    $stacks = StackDetector::detect(phpEcosystem([]), jsEcosystem([]));
    expect($stacks)->toBe([]);
});
