<?php

declare(strict_types=1);

use Laravel\Roster\Enums\Frontend;
use Laravel\Roster\Enums\JsPackageManager;
use Laravel\Roster\Enums\Stack;
use Laravel\Roster\Enums\TestFramework;
use Laravel\Roster\Roster;

it('scans the fog fixture end to end', function (): void {
    $path = __DIR__.'/../fixtures/fog';

    $roster = Roster::scan($path, detectSystem: false);

    expect($roster->php()->uses('pest'))->toBeTrue();
    expect($roster->php()->uses('pestphp/pest'))->toBeTrue();
    expect($roster->php()->uses('framework'))->toBeTrue();
    expect($roster->php()->uses('livewire/livewire'))->toBeTrue();
    expect($roster->php()->uses('pint'))->toBeTrue();

    expect($roster->js()->uses('tailwindcss'))->toBeTrue();
    expect($roster->js()->uses('@laravel/echo-react'))->toBeTrue();

    expect($roster->testFramework())->toBe(TestFramework::PEST);
    expect($roster->stack()->uses(Stack::LIVEWIRE))->toBeTrue();
    expect($roster->frontend()->uses(Frontend::VUE))->toBeFalse();

    expect($roster->js()->packageManagers()->configured()->uses(JsPackageManager::NPM))->toBeTrue();
});

it('renders json without error', function (): void {
    $path = __DIR__.'/../fixtures/fog';
    $roster = Roster::scan($path, detectSystem: false);
    $payload = json_decode($roster->json(), true);

    expect($payload)->toBeArray();
    expect($payload)->toHaveKey('php');
    expect($payload)->toHaveKey('js');
    expect($payload)->toHaveKey('stack');
    expect($payload)->toHaveKey('testFramework');
});
