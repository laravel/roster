<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use BackedEnum;
use Laravel\Roster\Enums\Agent;

/**
 * @extends MarkerDetector<Agent>
 */
class AgentsDetector extends MarkerDetector
{
    /** @var array<string, list<string>> */
    private const PROJECT_MARKERS = [
        Agent::CLAUDE_CODE->value => ['.claude', 'CLAUDE.md', '.claude.json'],
        Agent::CURSOR->value => ['.cursor', '.cursorrules'],
        Agent::CODEX->value => ['.codex', 'AGENTS.md'],
        Agent::COPILOT->value => ['.github/copilot-instructions.md'],
        Agent::GEMINI->value => ['.gemini', 'GEMINI.md'],
        Agent::JUNIE->value => ['.junie'],
        Agent::KIRO->value => ['.kiro'],
        Agent::OPENCODE->value => ['.opencode', 'opencode.json'],
        Agent::AMP->value => ['.amp', 'amp.json'],
        Agent::REPLIT->value => ['.replit', 'replit.nix'],
        Agent::DEVIN->value => ['.devin'],
        Agent::V0->value => ['.v0'],
        Agent::AUGMENT->value => ['.augment'],
        Agent::ANTIGRAVITY->value => ['.antigravity'],
        Agent::WINDSURF->value => ['.windsurf', '.windsurfrules'],
    ];

    /** @var array<string, list<string>> */
    private const SYSTEM_BINARIES = [
        Agent::CLAUDE_CODE->value => ['claude'],
        Agent::CURSOR->value => ['cursor'],
        Agent::CODEX->value => ['codex'],
        Agent::COPILOT->value => ['gh-copilot'],
        Agent::GEMINI->value => ['gemini'],
        Agent::JUNIE->value => ['junie'],
        Agent::KIRO->value => ['kiro'],
        Agent::OPENCODE->value => ['opencode'],
        Agent::AMP->value => ['amp'],
        Agent::WINDSURF->value => ['windsurf'],
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
        return Agent::from($value);
    }
}
