<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class CommandSignatureSyntax extends Convention
{
    protected function result(SourceFiles $files): ?ApproachResult
    {
        return $this->electByFile($files, 'Commands', fn (string $contents): array => [
            Approach::CommandAttributeSyntax->value => (int) preg_match_all('/#\[\s*AsCommand\b/', $contents),
            Approach::CommandPropertySyntax->value => (int) preg_match_all('/protected\s+(?:\S+\s+)*\$(?:signature|description)\b\s*=/', $contents),
        ]);
    }
}
