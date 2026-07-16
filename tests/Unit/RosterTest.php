<?php

declare(strict_types=1);

use Laravel\Roster\Enums\Frontend;
use Laravel\Roster\Enums\JsPackageManager;
use Laravel\Roster\Enums\Stack;
use Laravel\Roster\ProjectScan;

it('scans the fog fixture end to end', function (): void {
    $path = __DIR__.'/../fixtures/fog';

    $project = ProjectScan::scan($path);

    expect($project->php()->uses('pestphp/pest'))->toBeTrue();
    expect($project->php()->uses('laravel/framework'))->toBeTrue();
    expect($project->php()->uses('livewire/livewire'))->toBeTrue();
    expect($project->php()->uses('laravel/pint'))->toBeTrue();

    expect($project->js()->uses('tailwindcss'))->toBeTrue();
    expect($project->js()->uses('@laravel/echo-react'))->toBeTrue();

    expect($project->stacks()->uses(Stack::Livewire))->toBeTrue();
    expect($project->frontends()->uses(Frontend::Vue))->toBeFalse();

    expect($project->js()->packageManager())->toBe(JsPackageManager::Npm);
    expect($project->js()->usesPackageManager(JsPackageManager::Npm))->toBeTrue();
    expect($project->js()->usesPackageManager('npm'))->toBeTrue();
    expect($project->js()->usesPackageManager(JsPackageManager::Pnpm))->toBeFalse();
    expect($project->js()->usesPackageManager('not-a-package-manager'))->toBeFalse();
});

it('renders json without error', function (): void {
    $path = __DIR__.'/../fixtures/fog';
    $project = ProjectScan::scan($path);
    $payload = json_decode($project->json(), true);

    expect($payload)->toBeArray();
    expect($payload)->toHaveKey('php');
    expect($payload)->toHaveKey('js');
    expect($payload)->toHaveKey('stacks');
});
