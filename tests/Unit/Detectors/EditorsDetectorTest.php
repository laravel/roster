<?php

declare(strict_types=1);

use Laravel\Roster\Detectors\EditorsDetector;
use Laravel\Roster\Enums\Editor;

it('detects editors from filesystem markers', function (): void {
    $base = tempBase();
    mkdir($base.'.idea');
    mkdir($base.'.vscode');

    $detected = EditorsDetector::detect($base);
    expect($detected)->toContain(Editor::PhpStorm);
    expect($detected)->toContain(Editor::VsCode);
    expect($detected)->not->toContain(Editor::Zed);

    cleanup($base);
});

it('detects glob markers such as sublime project files', function (): void {
    $base = tempBase();
    touchFile($base.'app.sublime-project');

    $detected = EditorsDetector::detect($base);
    expect($detected)->toContain(Editor::SublimeText);

    cleanup($base);
});

it('detects glob markers when the project path contains glob metacharacters', function (): void {
    $base = tempBase();
    $tricky = $base.'[client]'.DIRECTORY_SEPARATOR;
    mkdir($tricky);
    touchFile($tricky.'app.sublime-project');

    $detected = EditorsDetector::detect($tricky);
    expect($detected)->toContain(Editor::SublimeText);

    cleanup($base);
});
