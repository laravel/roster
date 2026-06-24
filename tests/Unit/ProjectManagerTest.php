<?php

declare(strict_types=1);

use Laravel\Roster\Enums\Agent;
use Laravel\Roster\ProjectManager;
use Tests\TestCase;

uses(TestCase::class);

it('invalidates the cache when a project marker appears', function (): void {
    config()->set('cache.default', 'array');

    $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'roster_cache_'.uniqid().DIRECTORY_SEPARATOR;
    mkdir($base);

    $manager = new ProjectManager;

    expect($manager->scan($base)->agents()->all())->toBe([]);

    mkdir($base.'.claude');

    expect($manager->scan($base)->agents()->uses(Agent::CLAUDE_CODE))->toBeTrue();

    rmdir($base.'.claude');
    rmdir($base);
});
