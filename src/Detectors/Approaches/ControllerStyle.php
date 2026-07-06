<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class ControllerStyle extends Convention
{
    /**
     * @return list<ApproachResult>
     */
    public function detect(string $basePath, SourceFiles $files): array
    {
        $tally = [
            Approach::CONTROLLER_INVOKABLE->value => 0,
            Approach::CONTROLLER_MULTI_ACTION->value => 0,
        ];

        $paths = [];

        foreach ($files->php('Http/Controllers') as $path) {
            $contents = $files->contents($path);

            $invokable = preg_match('/function\s+__invoke\s*\(/', $contents) === 1;
            $actions = (int) preg_match_all('/public\s+function\s+(?!__)\w+\s*\(/', $contents);

            if (! $invokable && $actions < 2) {
                continue;
            }

            $tally[$invokable ? Approach::CONTROLLER_INVOKABLE->value : Approach::CONTROLLER_MULTI_ACTION->value]++;
            $paths[] = $path;
        }

        $result = $this->dominant($tally, $paths);

        return $result instanceof ApproachResult ? [$result] : [];
    }
}
