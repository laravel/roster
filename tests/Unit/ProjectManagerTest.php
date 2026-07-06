<?php

declare(strict_types=1);

use Laravel\Roster\Enums\Agent;
use Laravel\Roster\ProjectManager;
use Tests\TestCase;

uses(TestCase::class);

it('invalidates the cache when a project marker appears', function (): void {
    config()->set('cache.default', 'array');

    $base = tempBase();

    $manager = new ProjectManager;

    expect($manager->scan($base)->agents()->all())->toBe([]);

    mkdir($base.'.claude');

    expect($manager->scan($base)->agents()->uses(Agent::CLAUDE_CODE))->toBeTrue();

    cleanup($base);
});

it('invalidates the cache when lockfile contents change', function (): void {
    config()->set('cache.default', 'array');

    $base = tempBase();
    file_put_contents($base.'package.json', json_encode(['dependencies' => ['vue' => '^3.0']]));

    $manager = new ProjectManager;

    expect($manager->scan($base)->js()->uses('vue'))->toBeTrue();

    file_put_contents($base.'package.json', json_encode(['dependencies' => ['react' => '^19.0']]));

    $rescanned = $manager->scan($base);
    expect($rescanned->js()->uses('react'))->toBeTrue();
    expect($rescanned->js()->uses('vue'))->toBeFalse();

    cleanup($base);
});

it('returns the cached project for an unchanged directory', function (): void {
    config()->set('cache.default', 'array');

    $base = tempBase();

    $manager = new ProjectManager;

    expect($manager->scan($base))->toBe($manager->scan($base));

    cleanup($base);
});

it('fresh bypasses the cache and forces a re-read', function (): void {
    config()->set('cache.default', 'array');

    $base = tempBase();

    $manager = new ProjectManager;
    $cached = $manager->scan($base);

    expect($manager->fresh($base))->not->toBe($cached);

    cleanup($base);
});

it('scans without a usable cache driver', function (): void {
    config()->set('cache.default', 'null');

    $base = tempBase();
    file_put_contents($base.'package.json', json_encode(['dependencies' => ['vue' => '^3.0']]));

    expect((new ProjectManager)->scan($base)->js()->uses('vue'))->toBeTrue();

    cleanup($base);
});

it('does not repoint the default instance when scanning another directory', function (): void {
    config()->set('cache.default', 'array');

    $base = tempBase();
    mkdir($base.'.claude');

    $manager = new ProjectManager;
    $default = $manager->instance();

    expect($manager->scan($base)->agents()->uses(Agent::CLAUDE_CODE))->toBeTrue();

    expect($manager->instance())->toBe($default);
    expect($manager->agents()->uses(Agent::CLAUDE_CODE))->toBeFalse();

    cleanup($base);
});

it('memoizes the default instance', function (): void {
    $manager = new ProjectManager;

    expect($manager->instance())->toBe($manager->instance());
});
