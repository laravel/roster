<?php

use Laravel\Roster\Detectors\FrontendDetector;
use Laravel\Roster\Enums\Frontend;

it('detects frontends from npm packages', function (): void {
    $detection = (new FrontendDetector)->detect(jsEcosystem(['vue', 'react']));

    expect($detection->uses(Frontend::VUE))->toBeTrue();
    expect($detection->uses(Frontend::REACT))->toBeTrue();
    expect($detection->uses(Frontend::SVELTE))->toBeFalse();
});

it('returns empty when nothing present', function (): void {
    $detection = (new FrontendDetector)->detect(jsEcosystem([]));
    expect($detection->all())->toBe([]);
});

it('supports any-of via array argument', function (): void {
    $detection = (new FrontendDetector)->detect(jsEcosystem(['react']));
    expect($detection->uses([Frontend::VUE, Frontend::REACT]))->toBeTrue();
});
