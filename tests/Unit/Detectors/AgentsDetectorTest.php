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
    expect($detection->isConfigured(Agent::CLAUDE_CODE))->toBeTrue();
    expect($detection->isConfigured(Agent::CURSOR))->toBeTrue();
    expect($detection->isConfigured(Agent::PHPSTORM))->toBeFalse();

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

it('exposes agent kind helpers', function (): void {
    expect(Agent::PHPSTORM->isEditor())->toBeTrue();
    expect(Agent::PHPSTORM->isAi())->toBeFalse();
    expect(Agent::CLAUDE_CODE->isAi())->toBeTrue();
    expect(Agent::CLAUDE_CODE->isEditor())->toBeFalse();
});
