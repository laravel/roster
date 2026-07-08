<?php

declare(strict_types=1);

use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Project;

function approachesFixturePath(string $app): string
{
    return dirname(__DIR__).DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'approaches'.DIRECTORY_SEPARATOR.$app;
}

it('exposes approaches from a full project scan', function (): void {
    $project = Project::scan(approachesFixturePath('fillable-models-app'));

    expect($project->approaches()->uses(Approach::MassAssignmentFillable))->toBeTrue()
        ->and($project->approaches()->uses(Approach::MassAssignmentGuarded))->toBeFalse();
});

it('keeps the array payload cheap by omitting approaches', function (): void {
    $payload = Project::scan(approachesFixturePath('fillable-models-app'))->toArray();

    expect($payload)->not->toHaveKey('approaches');
});

it('strips computed approaches from the serialized payload', function (): void {
    $project = Project::scan(approachesFixturePath('fillable-models-app'));

    $project->approaches();

    expect($project->__serialize())
        ->not->toHaveKey('approaches')
        ->not->toHaveKey('approachExtensions')
        ->and(serialize($project))->not->toContain('ApproachSet');
});

it('recomputes approaches lazily after a serialize round-trip', function (): void {
    $project = Project::scan(approachesFixturePath('fillable-models-app'));

    $project->approaches();

    $restored = unserialize(serialize($project));

    expect($restored)->toBeInstanceOf(Project::class)
        ->and($restored)->not->toBe($project)
        ->and($restored->approaches()->uses(Approach::MassAssignmentFillable))->toBeTrue()
        ->and($restored->approaches()->uses(Approach::MassAssignmentGuarded))->toBeFalse();
});
