<?php

declare(strict_types=1);

namespace Laravel\Roster\Enums;

enum Agent: string
{
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
}
