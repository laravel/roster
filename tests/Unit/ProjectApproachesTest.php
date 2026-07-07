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

it('drops approaches on serialization and recomputes them lazily', function (): void {
    $project = Project::scan(approachesFixturePath('fillable-models-app'));

    $project->approaches();

    $restored = unserialize(serialize($project));

    expect($project->__serialize())->not->toHaveKey('approaches')
        ->and($restored)->toBeInstanceOf(Project::class)
        ->and($restored->approaches()->uses(Approach::MassAssignmentFillable))->toBeTrue();
});
