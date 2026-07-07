<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class ModelConfigSyntax extends Convention
{
    protected function result(string $basePath, SourceFiles $files): ?ApproachResult
    {
        return $this->electByFile($files, 'Models', fn (string $contents): array => [
            Approach::ModelAttributeSyntax->value => (int) preg_match_all('/#\[\s*(?:Fillable|Guarded|Hidden|Visible|Appends|Scope)\b/', $contents),
            Approach::ModelPropertySyntax->value => (int) preg_match_all('/protected\s+\$(?:fillable|guarded|hidden|visible|appends)\b/', $contents)
                + (int) preg_match_all('/function\s+scope[A-Z]\w*\s*\(/', $contents),
        ]);
    }
}
