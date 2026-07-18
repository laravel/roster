<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use Laravel\Roster\Enums\Agent;

/**
 * @extends MarkerDetector<Agent>
 */
class AgentsDetector extends MarkerDetector
{
    protected const ENUM = Agent::class;

    /** @var array<string, list<string>> */
    private const PROJECT_MARKERS = [
        Agent::ClaudeCode->value => ['.claude', 'CLAUDE.md', '.claude.json'],
        Agent::Cursor->value => ['.cursor', '.cursorrules'],
        Agent::Codex->value => ['.codex', 'AGENTS.md'],
        Agent::Copilot->value => ['.github/copilot-instructions.md'],
        Agent::Gemini->value => ['.gemini', 'GEMINI.md'],
        Agent::Junie->value => ['.junie'],
        Agent::Kiro->value => ['.kiro'],
        Agent::OpenCode->value => ['.opencode', 'opencode.json'],
        Agent::Amp->value => ['.amp', 'amp.json'],
        Agent::Replit->value => ['.replit', 'replit.nix'],
        Agent::Devin->value => ['.devin'],
        Agent::V0->value => ['.v0'],
        Agent::Augment->value => ['.augment'],
        Agent::Antigravity->value => ['.antigravity'],
        Agent::Windsurf->value => ['.windsurf', '.windsurfrules'],
    ];

    protected static function projectMarkers(): array
    {
        return self::PROJECT_MARKERS;
    }
}
