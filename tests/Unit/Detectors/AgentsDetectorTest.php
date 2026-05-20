<?php

declare(strict_types=1);

use Laravel\Roster\Detectors\AgentsDetector;
use Laravel\Roster\Enums\Agent;

it('detects configured agents from filesystem markers', function (): void {
    $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'roster_agents_'.uniqid().DIRECTORY_SEPARATOR;
    mkdir($base);
    mkdir($base.'.claude');
    mkdir($base.'.cursor');

    $configured = AgentsDetector::configured($base);
    expect($configured)->toContain(Agent::CLAUDE_CODE);
    expect($configured)->toContain(Agent::CURSOR);
    expect($configured)->not->toContain(Agent::CODEX);

    rmdir($base.'.claude');
    rmdir($base.'.cursor');
    rmdir($base);
});
