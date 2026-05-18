<?php

namespace Laravel\Roster\Enums;

enum Agent: string
{
    // AI agents / coding CLIs
    case CLAUDE_CODE = 'claude-code';
    case CURSOR = 'cursor';
    case CODEX = 'codex';
    case COPILOT = 'copilot';
    case GEMINI = 'gemini';
    case JUNIE = 'junie';
    case KIRO = 'kiro';
    case OPENCODE = 'opencode';
    case AMP = 'amp';
    case REPLIT = 'replit';
    case DEVIN = 'devin';
    case V0 = 'v0';
    case AUGMENT = 'augment';
    case ANTIGRAVITY = 'antigravity';
    case WINDSURF = 'windsurf';

    // Editors / IDEs
    case PHPSTORM = 'phpstorm';
    case VSCODE = 'vscode';

    public function isEditor(): bool
    {
        return match ($this) {
            self::PHPSTORM, self::VSCODE => true,
            default => false,
        };
    }

    public function isAi(): bool
    {
        return ! $this->isEditor();
    }
}
