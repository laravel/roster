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
        return $this->electByFile($files, 'Models', function (string $contents): ?array {
            if (! $this->isModel($contents)) {
                return null;
            }

            $uuid = preg_match('/\bHasUuids\b/', $contents) === 1;
            $ulid = preg_match('/\bHasUlids\b/', $contents) === 1;

            return [
                Approach::ModelUuidKeys->value => $uuid ? 1 : 0,
                Approach::ModelUlidKeys->value => $ulid ? 1 : 0,
                Approach::ModelIncrementingKeys->value => $uuid || $ulid ? 0 : 1,
            ];
        });
    }

    protected function isModel(string $contents): bool
    {
        if (preg_match('/^\s*(?:final\s+|abstract\s+|readonly\s+)*class\s+\w+/m', $contents) !== 1) {
            return false;
        }

        return preg_match('/\bextends\s+\w*(?:Model|Authenticatable|Pivot)\b/', $contents) === 1
            || preg_match('/\buse\s+Has(?:Uuids|Ulids|Factory)\b/', $contents) === 1
            || preg_match('/protected\s+(?:\S+\s+)*\$(?:table|fillable|guarded|casts|primaryKey|keyType|incrementing|timestamps|hidden|appends|dates)\b/', $contents) === 1;
    }
}
