<?php

namespace Laravel\Roster\Console;

use Illuminate\Console\Command;
use Laravel\Roster\Roster;

class ScanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roster:scan {directory}';

    protected $description = 'Detect packages & approaches in use and output as JSON';

    public function handle(): int
    {
        $directory = $this->argument('directory');
        if (! is_string($directory)) {
            $this->error('Pass a directory');

            return self::FAILURE;
        }

        if (! is_dir($directory) || ! is_readable($directory)) {
            $this->error("Directory '{$directory}' isn't a directory");

            return self::FAILURE;
        }

        $roster = Roster::scan($directory);
        $this->line($roster->json());

        $this->printMiniSummary($roster);

        return self::SUCCESS;
    }

    private function printMiniSummary(Roster $roster): void
    {
        $approaches_count = $roster->approaches()->count();
        $packages_count = $roster->packages()->count();

        $summary = match($approaches_count) {
            0 => [],
            1 => ["Approach: " . ucfirst($roster->approaches()->first()?->approach()->value ?? "") . "."],
            default => ["Approaches: $approaches_count."]
        };

        $summary[] = match($packages_count) {
            0 => "",
            1 => "Package: " . ucfirst(strtolower($roster->packages()->first()?->name() ?? "")),
            default => "Packages: $packages_count."
        };

        $this->line(implode(" ", $summary));
    }
}
