<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class ModelConfigSyntax extends Convention
{
    protected function result(string $basePath, SourceFiles $files): ?ApproachResult
    {
        $tally = [
            Approach::ModelAttributeSyntax->value => 0,
            Approach::ModelPropertySyntax->value => 0,
        ];

        $paths = [];

        foreach ($files->php('Models') as $path) {
            $contents = $files->contents($path);

            $winner = $this->fileVote([
                Approach::ModelAttributeSyntax->value => (int) preg_match_all('/#\[\s*(?:Fillable|Guarded|Hidden|Visible|Appends|Scope)\b/', $contents),
                Approach::ModelPropertySyntax->value => (int) preg_match_all('/protected\s+\$(?:fillable|guarded|hidden|visible|appends)\b/', $contents)
                    + (int) preg_match_all('/function\s+scope[A-Z]\w*\s*\(/', $contents),
            ]);

            if ($winner === null) {
                continue;
            }

            $tally[$winner]++;
            $paths[] = $path;
        }

        return $this->dominant($tally, $paths);
    }
}
