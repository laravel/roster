<?php

namespace Laravel\Roster\Detectors;

use Laravel\Roster\Enums\Agent;
use Laravel\Roster\Support\SystemProbe;

class AgentsDetector
{
    /**
     * Project-level markers. The presence of any marker for an agent counts as configured.
     *
     * @var array<string, list<string>>
     */
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
        Agent::PHPSTORM->value => ['.idea'],
        Agent::VSCODE->value => ['.vscode'],
    ];

    /**
     * System binaries that indicate an agent is installed.
     *
     * @var array<string, list<string>>
     */
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
        Agent::PHPSTORM->value => ['phpstorm'],
        Agent::VSCODE->value => ['code'],
    ];

    public function __construct(
        protected string $basePath,
        protected bool $detectSystem = true,
    ) {}

    public function detect(): AgentsDetection
    {
        return new AgentsDetection(
            $this->detectFromProjectMarkers(),
            $this->detectSystem ? $this->detectFromSystemBinaries() : [],
        );
    }

    /**
     * @return list<Agent>
     */
    private function detectFromProjectMarkers(): array
    {
        $configured = [];

        foreach (self::PROJECT_MARKERS as $agentValue => $markers) {
            foreach ($markers as $marker) {
                if (SystemProbe::pathExists($this->basePath.str_replace('/', DIRECTORY_SEPARATOR, $marker))) {
                    $configured[] = Agent::from($agentValue);

                    break;
                }
            }
        }

        return $configured;
    }

    /**
     * @return list<Agent>
     */
    private function detectFromSystemBinaries(): array
    {
        $installed = [];

        foreach (self::SYSTEM_BINARIES as $agentValue => $binaries) {
            foreach ($binaries as $binary) {
                if (SystemProbe::commandExists($binary)) {
                    $installed[] = Agent::from($agentValue);

                    break;
                }
            }
        }

        return $installed;
    }
}
