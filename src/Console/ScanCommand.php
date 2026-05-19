<?php

declare(strict_types=1);

namespace Laravel\Roster\Console;

use Illuminate\Console\Command;
use Laravel\Roster\Project;
use Laravel\Roster\System;

class ScanCommand extends Command
{
    protected $signature = 'roster:scan {directory} {--no-system : Skip system probes}';

    protected $description = 'Detect packages, stacks, frameworks, and agents in use and output as JSON';

    public function handle(): int
    {
        $directory = $this->argument('directory');

        if (! is_string($directory)) {
            $this->error('Pass a directory.');

            return self::FAILURE;
        }

        if (! is_dir($directory) || ! is_readable($directory)) {
            $this->error("Directory '{$directory}' is not a readable directory.");

            return self::FAILURE;
        }

        $payload = Project::scan($directory)->toArray();

        if (! $this->option('no-system')) {
            $payload['system'] = System::scan()->toArray();
        }

        $this->line(json_encode($payload, JSON_PRETTY_PRINT) ?: '{}');

        return self::SUCCESS;
    }
}
