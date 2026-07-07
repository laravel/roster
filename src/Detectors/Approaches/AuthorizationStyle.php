<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class AuthorizationStyle extends Convention
{
    protected function result(string $basePath, SourceFiles $files): ?ApproachResult
    {
        $tally = [
            Approach::AuthorizationGate->value => 0,
            Approach::AuthorizationUserCan->value => 0,
            Approach::AuthorizationAttribute->value => 0,
            Approach::AuthorizationTrait->value => 0,
        ];

        $paths = [];

        foreach ($files->php('Http/Controllers') as $path) {
            $contents = $files->contents($path);

            $winner = $this->fileVote([
                Approach::AuthorizationGate->value => (int) preg_match_all('/\bGate::(?:authorize|allows|denies|any|none|check|inspect)\s*\(/', $contents),
                Approach::AuthorizationUserCan->value => (int) preg_match_all('/->user\(\)->(?:can|cannot)\s*\(/', $contents),
                Approach::AuthorizationAttribute->value => (int) preg_match_all('/#\[\s*Authorize\b/', $contents),
                Approach::AuthorizationTrait->value => (int) preg_match_all('/\$this->authorize\s*\(/', $contents),
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
