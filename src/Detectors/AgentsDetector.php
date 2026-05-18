<?php

namespace Laravel\Roster\Detectors;

use Laravel\Roster\Enums\Agent;
use Laravel\Roster\Support\SystemProbe;

class AgentsDetector
{
    /** @var array<string, list<string>> Any path's presence counts as configured. */
    protected array $projectMarkers = [
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

    /** @var array<string, array{binaries?: list<string>, paths?: list<string>}> */
    protected array $systemMarkers = [
        Agent::CLAUDE_CODE->value => ['binaries' => ['claude']],
        Agent::CURSOR->value => ['binaries' => ['cursor']],
        Agent::CODEX->value => ['binaries' => ['codex']],
        Agent::COPILOT->value => ['binaries' => ['gh-copilot']],
        Agent::GEMINI->value => ['binaries' => ['gemini']],
        Agent::JUNIE->value => ['binaries' => ['junie']],
        Agent::KIRO->value => ['binaries' => ['kiro']],
        Agent::OPENCODE->value => ['binaries' => ['opencode']],
        Agent::AMP->value => ['binaries' => ['amp']],
        Agent::WINDSURF->value => ['binaries' => ['windsurf']],
        Agent::PHPSTORM->value => ['binaries' => ['phpstorm']],
        Agent::VSCODE->value => ['binaries' => ['code']],
    ];

    public function __construct(
        protected string $basePath,
        protected bool $detectSystem = true,
    ) {}

    public function detect(): AgentsDetection
    {
        $configured = [];
        foreach ($this->projectMarkers as $value => $markers) {
            foreach ($markers as $marker) {
                if (SystemProbe::pathExists($this->basePath.str_replace('/', DIRECTORY_SEPARATOR, $marker))) {
                    $configured[] = Agent::from($value);
                    break;
                }
            }
        }

        $installed = [];
        if ($this->detectSystem) {
            foreach ($this->systemMarkers as $value => $probes) {
                if ($this->probeMatches($probes)) {
                    $installed[] = Agent::from($value);
                }
            }
        }

        return new AgentsDetection($configured, $installed);
    }

    /**
     * @param  array{binaries?: list<string>, paths?: list<string>}  $probes
     */
    private function probeMatches(array $probes): bool
    {
        foreach ($probes['binaries'] ?? [] as $binary) {
            if (SystemProbe::commandExists($binary)) {
                return true;
            }
        }

        foreach ($probes['paths'] ?? [] as $path) {
            if (SystemProbe::pathExists($path)) {
                return true;
            }
        }

        return false;
    }
}
