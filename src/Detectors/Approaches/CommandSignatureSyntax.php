<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class CommandSignatureSyntax extends Convention
{
    protected function result(string $basePath, SourceFiles $files): ?ApproachResult
    {
        $tally = [
            Approach::COMMAND_ATTRIBUTE_SYNTAX->value => 0,
            Approach::COMMAND_PROPERTY_SYNTAX->value => 0,
        ];

        $paths = [];

        foreach ($files->php('Commands') as $path) {
            $contents = $files->contents($path);

            $attributes = (int) preg_match_all('/#\[\s*(?:Signature|Description)\b/', $contents);
            $properties = (int) preg_match_all('/protected\s+\$(?:signature|description)\b\s*=/', $contents);

            if ($attributes === 0 && $properties === 0) {
                continue;
            }

            $tally[Approach::COMMAND_ATTRIBUTE_SYNTAX->value] += $attributes;
            $tally[Approach::COMMAND_PROPERTY_SYNTAX->value] += $properties;
            $paths[] = $path;
        }

        return $this->dominant($tally, $paths);
    }
}
