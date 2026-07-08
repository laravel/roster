<?php

declare(strict_types=1);

namespace Laravel\Roster\Console;

use Illuminate\Console\Command;
use Laravel\Roster\ApproachResult;
use Laravel\Roster\ProjectManager;
use Laravel\Roster\ProjectScan;

class ScanCommand extends Command
{
    protected $signature = 'roster:scan {directory? : The directory to scan (defaults to the application base path)} {--approaches : Detect source-code approaches (scans every PHP source file)}';

    protected $description = 'Detect packages, stacks, frameworks, agents, and approaches in use and output as JSON';

    public function handle(ProjectManager $projects): int
    {
        $directory = $this->argument('directory') ?? ProjectScan::normalizeBasePath(null);

        if (! is_string($directory)) {
            $this->error('Pass a directory.');

            return self::FAILURE;
        }

        if (! is_dir($directory) || ! is_readable($directory)) {
            $this->error("Directory '{$directory}' is not a readable directory.");

            return self::FAILURE;
        }

        $project = $projects->fresh($directory);
        $payload = $project->toArray();

        if ($this->option('approaches')) {
            $payload['approaches'] = $project->approaches()->all()
                ->map(fn (ApproachResult $result): array => $result->toArray())
                ->all();
        }

        $this->line(ProjectScan::encode($payload));

        return self::SUCCESS;
    }
}
