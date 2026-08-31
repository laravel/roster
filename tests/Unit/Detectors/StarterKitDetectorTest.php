<?php

declare(strict_types=1);

use Laravel\Roster\Detectors\StarterKitDetector;
use Laravel\Roster\ProjectScan;

it('detects the declared starter kit', function (): void {
    $base = tempBase();
    file_put_contents($base.'composer.json', json_encode([
        'extra' => ['laravel' => ['starter-kit' => 'laravel/agent-kit']],
    ]));

    expect(StarterKitDetector::detect($base))->toBe('laravel/agent-kit');
});

it('exposes the starter kit through the project scan', function (): void {
    $base = tempBase();
    file_put_contents($base.'composer.json', json_encode([
        'extra' => ['laravel' => ['starter-kit' => 'laravel/agent-kit']],
    ]));

    $project = ProjectScan::scan($base);

    expect($project->starterKit())->toBe('laravel/agent-kit')
        ->and($project->toArray()['starterKit'])->toBe('laravel/agent-kit')
        ->and(unserialize(serialize($project))->starterKit())->toBe('laravel/agent-kit');
});

it('returns null without a composer.json', function (): void {
    expect(StarterKitDetector::detect(tempBase()))->toBeNull();
});

it('returns null when composer.json is malformed', function (): void {
    $base = tempBase();
    file_put_contents($base.'composer.json', '{not json');

    expect(StarterKitDetector::detect($base))->toBeNull();
});

it('returns null without a starter kit declaration', function (): void {
    $base = tempBase();
    file_put_contents($base.'composer.json', json_encode([
        'extra' => ['laravel' => ['dont-discover' => []]],
    ]));

    expect(StarterKitDetector::detect($base))->toBeNull();
});

it('returns null for declarations that are not a non-empty string', function (mixed $value): void {
    $base = tempBase();
    file_put_contents($base.'composer.json', json_encode([
        'extra' => ['laravel' => ['starter-kit' => $value]],
    ]));

    expect(StarterKitDetector::detect($base))->toBeNull();
})->with([[''], [123], [['laravel/agent-kit']], [true], [null]]);
