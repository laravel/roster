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
        return $this->electByFile($files, 'Models', fn (string $contents): array => [
            Approach::ModelUuidKeys->value => preg_match('/\bHasUuids\b/', $contents) === 1 ? 1 : 0,
            Approach::ModelUlidKeys->value => preg_match('/\bHasUlids\b/', $contents) === 1 ? 1 : 0,
        ]);
    }
}
