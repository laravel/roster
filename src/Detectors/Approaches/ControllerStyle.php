<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class ControllerStyle extends Convention
{
    protected function result(string $basePath, SourceFiles $files): ?ApproachResult
    {
        $tally = [
            Approach::ControllerInvokable->value => 0,
            Approach::ControllerMultiAction->value => 0,
        ];

        $paths = [];

        foreach ($files->php('Http/Controllers') as $path) {
            $contents = $files->contents($path);

            $invokable = preg_match('/function\s+__invoke\s*\(/', $contents) === 1;
            $actions = (int) preg_match_all('/public\s+function\s+(?!__)\w+\s*\(/', $contents);

            if (! $invokable && $actions < 2) {
                continue;
            }

            $tally[$invokable ? Approach::ControllerInvokable->value : Approach::ControllerMultiAction->value]++;
            $paths[] = $path;
        }

        return $this->dominant($tally, $paths);
    }
}
