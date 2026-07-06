<?php

declare(strict_types=1);

use Laravel\Roster\Detectors\AgentsDetector;
use Laravel\Roster\Enums\Agent;

it('detects configured agents from filesystem markers', function (): void {
    $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'roster_agents_'.uniqid().DIRECTORY_SEPARATOR;
    mkdir($base);
    mkdir($base.'.claude');
    mkdir($base.'.cursor');

    $detection = (new AgentsDetector($base, detectSystem: false))->detect();
    expect($detection->configured()->is(Agent::CLAUDE_CODE))->toBeTrue();
    expect($detection->configured()->is(Agent::CURSOR))->toBeTrue();
    expect($detection->configured()->is(Agent::PHPSTORM))->toBeFalse();

    rmdir($base.'.claude');
    rmdir($base.'.cursor');
    rmdir($base);
});

it('skips installed probes when detectSystem is false', function (): void {
    $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'roster_agents_nosys_'.uniqid().DIRECTORY_SEPARATOR;
    mkdir($base);

    $detection = (new AgentsDetector($base, detectSystem: false))->detect();
    expect($detection->installed()->all())->toBe([]);

    rmdir($base);
});
