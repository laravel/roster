<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class MassAssignment extends Convention
{
    protected function result(string $basePath, SourceFiles $files): ?ApproachResult
    {
        $tally = [
            Approach::MASS_ASSIGNMENT_FILLABLE->value => 0,
            Approach::MASS_ASSIGNMENT_GUARDED->value => 0,
        ];

        $paths = [];

        foreach ($files->php('Models') as $path) {
            $contents = $files->contents($path);

            $fillable = preg_match('/protected\s+\$fillable\b/', $contents) === 1
                || preg_match('/#\[\s*Fillable\b/', $contents) === 1;
            $guarded = preg_match('/protected\s+\$guarded\b/', $contents) === 1
                || preg_match('/#\[\s*Guarded\b/', $contents) === 1;

            if ($fillable === $guarded) {
                continue;
            }

            $tally[$fillable ? Approach::MASS_ASSIGNMENT_FILLABLE->value : Approach::MASS_ASSIGNMENT_GUARDED->value]++;
            $paths[] = $path;
        }

        return $this->dominant($tally, $paths);
    }
}
