<?php

declare(strict_types=1);

namespace Laravel\Roster\Enums;

enum Agent: string
{
    case ClaudeCode = 'claude-code';
    case Cursor = 'cursor';
    case Codex = 'codex';
    case Copilot = 'copilot';
    case Gemini = 'gemini';
    case Junie = 'junie';
    case Kiro = 'kiro';
    case OpenCode = 'opencode';
    case Amp = 'amp';
    case Replit = 'replit';
    case Devin = 'devin';
    case V0 = 'v0';
    case Augment = 'augment';
    case Antigravity = 'antigravity';
    case Windsurf = 'windsurf';
}
