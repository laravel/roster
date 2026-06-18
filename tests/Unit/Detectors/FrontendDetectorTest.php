<?php

declare(strict_types=1);

use Laravel\Roster\Detectors\FrontendDetector;
use Laravel\Roster\Enums\Frontend;

it('detects frontends from npm packages', function (): void {
    $frontends = FrontendDetector::detect(jsEcosystem(['vue', 'react']));

    expect($frontends)->toContain(Frontend::VUE);
    expect($frontends)->toContain(Frontend::REACT);
    expect($frontends)->not->toContain(Frontend::SVELTE);
});

it('returns empty when nothing present', function (): void {
    expect(FrontendDetector::detect(jsEcosystem([])))->toBe([]);
});

it('ignores a transitively installed frontend package', function (): void {
    $frontends = FrontendDetector::detect(jsEcosystem([
        ['name' => 'react', 'direct' => false],
    ]));

    expect($frontends)->toBe([]);
});
