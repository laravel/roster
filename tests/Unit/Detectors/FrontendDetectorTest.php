<?php

declare(strict_types=1);

use Laravel\Roster\Detectors\FrontendDetector;
use Laravel\Roster\Enums\Frontend;

it('detects frontends from npm packages', function (): void {
    $frontends = FrontendDetector::detect(jsEcosystem(['vue', 'react']));

    expect($frontends)->toContain(Frontend::Vue);
    expect($frontends)->toContain(Frontend::React);
    expect($frontends)->not->toContain(Frontend::Svelte);
});

it('returns empty when nothing present', function (): void {
    expect(FrontendDetector::detect(jsEcosystem([])))->toBe([]);
});

it('detects a frontend from an adapter marker without the core package', function (): void {
    $frontends = FrontendDetector::detect(jsEcosystem(['@inertiajs/svelte']));

    expect($frontends)->toBe([Frontend::Svelte]);
});

it('ignores a transitively installed frontend package', function (): void {
    $frontends = FrontendDetector::detect(jsEcosystem([
        ['name' => 'react', 'direct' => false],
    ]));

    expect($frontends)->toBe([]);
});
