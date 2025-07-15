<?php

namespace Laravel\Using\Console;

use Illuminate\Console\Command;
use Laravel\Using\Using;

class CheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'using:check {directory}';

    protected $description = 'Detect packages & approaches in use';

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

        $items = Using::scan($directory);
        dump($items);

        return self::SUCCESS;
    }
}
