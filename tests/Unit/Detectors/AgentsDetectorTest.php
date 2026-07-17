<?php

declare(strict_types=1);

use Laravel\Roster\Detectors\AgentsDetector;
use Laravel\Roster\Enums\Agent;

it('detects configured agents from filesystem markers', function (): void {
    $base = tempBase();
    mkdir($base.'.claude');
    mkdir($base.'.cursor');

    $detected = AgentsDetector::detect($base);
    expect($detected)->toContain(Agent::ClaudeCode);
    expect($detected)->toContain(Agent::Cursor);
    expect($detected)->not->toContain(Agent::Codex);

    cleanup($base);
});
