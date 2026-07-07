<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class AuthRetrievalStyle extends Convention
{
    protected function result(string $basePath, SourceFiles $files): ?ApproachResult
    {
        $tally = [
            Approach::AuthFacade->value => 0,
            Approach::AuthRequest->value => 0,
            Approach::AuthHelper->value => 0,
        ];

        $paths = [];

        foreach ($files->php() as $path) {
            $contents = $files->contents($path);

            $winner = $this->fileVote([
                Approach::AuthFacade->value => (int) preg_match_all('/\bAuth::(?:user|id|check|guest)\s*\(/', $contents),
                Approach::AuthRequest->value => (int) preg_match_all('/\$request->user\(\)/', $contents),
                Approach::AuthHelper->value => (int) preg_match_all('/\bauth\(\)->(?:user|id|check|guest)\s*\(/', $contents),
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
