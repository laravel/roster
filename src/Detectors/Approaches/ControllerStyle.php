<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class ControllerStyle extends Convention
{
    /** @var list<string> */
    private const RESOURCE_ACTIONS = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];

    protected function result(SourceFiles $files): ?ApproachResult
    {
        $tally = [
            Approach::ControllerInvokable->value => 0,
            Approach::ControllerResourceful->value => 0,
        ];

        $paths = [];

        foreach ($files->php('Http/Controllers') as $path) {
            $contents = $files->contents($path);

            if (preg_match('/function\s+__invoke\s*\(/', $contents) === 1) {
                $tally[Approach::ControllerInvokable->value]++;
                $paths[] = $path;

                continue;
            }

            if ($this->resourceActionCount($contents) >= 2) {
                $tally[Approach::ControllerResourceful->value]++;
                $paths[] = $path;
            }
        }

        return $this->dominant($tally, $paths);
    }

    private function resourceActionCount(string $contents): int
    {
        $count = 0;

        foreach (self::RESOURCE_ACTIONS as $action) {
            if (preg_match('/public\s+function\s+'.$action.'\s*\(/', $contents) === 1) {
                $count++;
            }
        }

        return $count;
    }
}
