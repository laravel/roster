<?php

declare(strict_types=1);

use Laravel\Roster\Enums\Frontend;
use Laravel\Roster\Enums\JsPackageManager;
use Laravel\Roster\Enums\Stack;
use Laravel\Roster\Enums\TestFramework;
use Laravel\Roster\Project;

it('scans the fog fixture end to end', function (): void {
    $path = __DIR__.'/../fixtures/fog';

    $project = Project::scan($path);

    expect($project->php()->uses('pest'))->toBeTrue();
    expect($project->php()->uses('pestphp/pest'))->toBeTrue();
    expect($project->php()->uses('framework'))->toBeTrue();
    expect($project->php()->uses('livewire/livewire'))->toBeTrue();
    expect($project->php()->uses('pint'))->toBeTrue();

    expect($project->js()->uses('tailwindcss'))->toBeTrue();
    expect($project->js()->uses('@laravel/echo-react'))->toBeTrue();

    expect($project->testFramework())->toBe(TestFramework::PEST);
    expect($project->stack()->uses(Stack::LIVEWIRE))->toBeTrue();
    expect($project->frontend()->uses(Frontend::VUE))->toBeFalse();

    expect($project->js()->packageManager()?->is(JsPackageManager::NPM))->toBeTrue();
});

it('renders json without error', function (): void {
    $path = __DIR__.'/../fixtures/fog';
    $project = Project::scan($path);
    $payload = json_decode($project->json(), true);

    expect($payload)->toBeArray();
    expect($payload)->toHaveKey('php');
    expect($payload)->toHaveKey('js');
    expect($payload)->toHaveKey('stack');
    expect($payload)->toHaveKey('testFramework');
});
