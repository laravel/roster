<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class ModelKeyStyle extends Convention
{
    /**
     * @return list<ApproachResult>
     */
    public function detect(string $basePath, SourceFiles $files): array
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

        $result = $this->dominant($tally, $paths);

        return $result instanceof ApproachResult ? [$result] : [];
    }
}
