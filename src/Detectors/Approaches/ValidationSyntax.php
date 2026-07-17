<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class ValidationSyntax extends Convention
{
    protected function result(SourceFiles $files): ?ApproachResult
    {
        return $this->electByFile($files, 'Http/Requests', fn (string $contents): ?array => str_contains($contents, 'function rules') ? [
            Approach::ValidationPipeSyntax->value => (int) preg_match_all("/=>\s*'(?![^']*regex:)[^']*\|[^']*'/", $contents),
            Approach::ValidationArraySyntax->value => (int) preg_match_all("/=>\s*\[\s*'/", $contents),
        ] : null);
    }
}
