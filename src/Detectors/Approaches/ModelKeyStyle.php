<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class ModelKeyStyle extends Convention
{
    protected function result(string $basePath, SourceFiles $files): ?ApproachResult
    {
        $tally = [
            Approach::MODEL_UUID_KEYS->value => 0,
            Approach::MODEL_ULID_KEYS->value => 0,
        ];

        $paths = [];

        foreach ($files->php('Models') as $path) {
            $contents = $files->contents($path);

            $uuid = preg_match('/\bHasUuids\b/', $contents) === 1;
            $ulid = preg_match('/\bHasUlids\b/', $contents) === 1;

            if ($uuid === $ulid) {
                continue;
            }

            $tally[$uuid ? Approach::MODEL_UUID_KEYS->value : Approach::MODEL_ULID_KEYS->value]++;
            $paths[] = $path;
        }

        return $this->dominant($tally, $paths);
    }
}
