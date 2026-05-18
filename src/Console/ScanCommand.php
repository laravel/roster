<?php

declare(strict_types=1);

namespace Laravel\Roster\Console;

use Illuminate\Console\Command;
use Laravel\Roster\Roster;

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

        $roster = Roster::scan($directory, detectSystem: ! $this->option('no-system'));
        $this->line($roster->json());

        return self::SUCCESS;
    }
}
