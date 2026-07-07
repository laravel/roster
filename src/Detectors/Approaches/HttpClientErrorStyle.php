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
        return $this->electByFile($files, null, fn (string $contents): ?array => str_contains($contents, 'Http::') ? [
            Approach::HttpClientThrow->value => (int) preg_match_all('/->throw(?:If|Unless|IfStatus|UnlessStatus|IfServerError|IfClientError)?\s*\(/', $contents),
            Approach::HttpClientStatusCheck->value => (int) preg_match_all('/->(?:successful|failed|clientError|serverError)\s*\(\s*\)/', $contents),
        ] : null);
    }
}
