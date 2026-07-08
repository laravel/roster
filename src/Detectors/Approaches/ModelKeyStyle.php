<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class ModelKeyStyle extends Convention
{
    protected function result(SourceFiles $files): ?ApproachResult
    {
        return $this->electByFile($files, 'Models', function (string $contents): array {
            $uuid = preg_match('/\bHasUuids\b/', $contents) === 1;
            $ulid = preg_match('/\bHasUlids\b/', $contents) === 1;

            return [
                Approach::ModelUuidKeys->value => $uuid ? 1 : 0,
                Approach::ModelUlidKeys->value => $ulid ? 1 : 0,
                Approach::ModelIncrementingKeys->value => $uuid || $ulid ? 0 : 1,
            ];
        });
    }
}
