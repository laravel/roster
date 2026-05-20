<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use BackedEnum;
use Laravel\Roster\Enums\Editor;

/**
 * @extends MarkerDetector<Editor>
 */
class EditorsDetector extends MarkerDetector
{
    /** @var array<string, list<string>> */
    private const PROJECT_MARKERS = [
        Editor::PHPSTORM->value => ['.idea'],
        Editor::VSCODE->value => ['.vscode'],
        Editor::ZED->value => ['.zed'],
        Editor::SUBLIME_TEXT->value => ['*.sublime-project', '*.sublime-workspace'],
    ];

    /** @var array<string, list<string>> */
    private const SYSTEM_BINARIES = [
        Editor::PHPSTORM->value => ['phpstorm'],
        Editor::VSCODE->value => ['code'],
        Editor::ZED->value => ['zed'],
        Editor::SUBLIME_TEXT->value => ['subl'],
    ];

    protected static function projectMarkers(): array
    {
        return self::PROJECT_MARKERS;
    }

    protected static function systemBinaries(): array
    {
        return self::SYSTEM_BINARIES;
    }

    protected static function fromValue(string $value): BackedEnum
    {
        return Editor::from($value);
    }
}
