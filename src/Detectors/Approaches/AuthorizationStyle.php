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
        return $this->electByFile($files, 'Http/Controllers', fn (string $contents): array => [
            Approach::AuthorizationGate->value => (int) preg_match_all('/\bGate::(?:authorize|allows|denies|any|none|check|inspect)\s*\(/', $contents),
            Approach::AuthorizationUserCan->value => (int) preg_match_all('/->user\(\)->(?:can|cannot)\s*\(/', $contents),
            Approach::AuthorizationAttribute->value => (int) preg_match_all('/#\[\s*Authorize\b/', $contents),
            Approach::AuthorizationTrait->value => (int) preg_match_all('/\$this->authorize\s*\(/', $contents),
        ]);
    }
}
