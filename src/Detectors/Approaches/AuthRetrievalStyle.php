<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class AuthRetrievalStyle extends Convention
{
    protected function result(SourceFiles $files): ?ApproachResult
    {
        return $this->electByFile($files, null, fn (string $contents): array => [
            Approach::AuthFacade->value => (int) preg_match_all('/\bAuth::(?:user|id|check|guest)\s*\(/', $contents),
            Approach::AuthRequest->value => (int) preg_match_all('/\$request->user\(\)/', $contents),
            Approach::AuthHelper->value => (int) preg_match_all('/\bauth\(\)->(?:user|id|check|guest)\s*\(/', $contents),
        ]);
    }
}
