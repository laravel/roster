<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use Laravel\Roster\Enums\Editor;

/**
 * @extends MarkerDetector<Editor>
 */
class EditorsDetector extends MarkerDetector
{
    protected const ENUM = Editor::class;

    /** @var array<string, list<string>> */
    private const PROJECT_MARKERS = [
        Editor::PhpStorm->value => ['.idea'],
        Editor::VsCode->value => ['.vscode'],
        Editor::Zed->value => ['.zed'],
        Editor::SublimeText->value => ['*.sublime-project', '*.sublime-workspace'],
    ];

    protected static function projectMarkers(): array
    {
        return self::PROJECT_MARKERS;
    }
}
