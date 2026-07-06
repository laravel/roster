<?php

declare(strict_types=1);

use Laravel\Roster\Detectors\AgentsDetector;
use Laravel\Roster\Enums\Agent;

it('detects configured agents from filesystem markers', function (): void {
    $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'roster_agents_'.uniqid().DIRECTORY_SEPARATOR;
    mkdir($base);
    mkdir($base.'.claude');
    mkdir($base.'.cursor');

    $detected = AgentsDetector::detect($base);
    expect($detected)->toContain(Agent::CLAUDE_CODE);
    expect($detected)->toContain(Agent::CURSOR);
    expect($detected)->not->toContain(Agent::CODEX);

    rmdir($base.'.claude');
    rmdir($base.'.cursor');
    rmdir($base);
});
