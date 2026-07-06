<?php

declare(strict_types=1);

use Laravel\Roster\Detectors\EditorsDetector;
use Laravel\Roster\Enums\Editor;

it('detects editors from filesystem markers', function (): void {
    $base = tempBase();
    mkdir($base.'.idea');
    mkdir($base.'.vscode');

    $detected = EditorsDetector::detect($base);
    expect($detected)->toContain(Editor::PHPSTORM);
    expect($detected)->toContain(Editor::VSCODE);
    expect($detected)->not->toContain(Editor::ZED);

    cleanup($base);
});

it('detects glob markers such as sublime project files', function (): void {
    $base = tempBase();
    touchFile($base.'app.sublime-project');

    $detected = EditorsDetector::detect($base);
    expect($detected)->toContain(Editor::SUBLIME_TEXT);

    cleanup($base);
});
