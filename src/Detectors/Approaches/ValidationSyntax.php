<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class ValidationSyntax extends Convention
{
    protected function result(string $basePath, SourceFiles $files): ?ApproachResult
    {
        $tally = [
            Approach::VALIDATION_PIPE_SYNTAX->value => 0,
            Approach::VALIDATION_ARRAY_SYNTAX->value => 0,
        ];

        $paths = [];

        foreach ($files->php('Http/Requests') as $path) {
            $contents = $files->contents($path);

            if (! str_contains($contents, 'function rules')) {
                continue;
            }

            $pipe = (int) preg_match_all("/=>\s*'[^']*\|[^']*'/", $contents);
            $array = (int) preg_match_all("/=>\s*\[\s*'/", $contents);

            if ($pipe === $array) {
                continue;
            }

            $tally[$pipe > $array ? Approach::VALIDATION_PIPE_SYNTAX->value : Approach::VALIDATION_ARRAY_SYNTAX->value]++;
            $paths[] = $path;
        }

        return $this->dominant($tally, $paths);
    }
}
