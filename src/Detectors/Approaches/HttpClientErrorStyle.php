<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class HttpClientErrorStyle extends Convention
{
    protected function result(string $basePath, SourceFiles $files): ?ApproachResult
    {
        $tally = [
            Approach::HttpClientThrow->value => 0,
            Approach::HttpClientStatusCheck->value => 0,
        ];

        $paths = [];

        foreach ($files->php() as $path) {
            $contents = $files->contents($path);

            if (! str_contains($contents, 'Http::')) {
                continue;
            }

            $winner = $this->fileVote([
                Approach::HttpClientThrow->value => (int) preg_match_all('/->throw(?:If|Unless|IfStatus|UnlessStatus|IfServerError|IfClientError)?\s*\(/', $contents),
                Approach::HttpClientStatusCheck->value => (int) preg_match_all('/->(?:successful|failed|clientError|serverError)\s*\(\s*\)/', $contents),
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
