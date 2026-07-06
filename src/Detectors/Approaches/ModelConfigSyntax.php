<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class ModelConfigSyntax extends Convention
{
    /**
     * @return list<ApproachResult>
     */
    public function detect(string $basePath, SourceFiles $files): array
    {
        $tally = [
            Approach::MODEL_ATTRIBUTE_SYNTAX->value => 0,
            Approach::MODEL_PROPERTY_SYNTAX->value => 0,
        ];

        $paths = [];

        foreach ($files->php('Models') as $path) {
            $contents = $files->contents($path);

            $attributes = (int) preg_match_all('/#\[\s*(?:Fillable|Guarded|Hidden|Visible|Appends|Scope)\b/', $contents);
            $properties = (int) preg_match_all('/protected\s+\$(?:fillable|guarded|hidden|visible|appends)\b/', $contents)
                + (int) preg_match_all('/function\s+scope[A-Z]\w*\s*\(/', $contents);

            if ($attributes === 0 && $properties === 0) {
                continue;
            }

            $tally[Approach::MODEL_ATTRIBUTE_SYNTAX->value] += $attributes;
            $tally[Approach::MODEL_PROPERTY_SYNTAX->value] += $properties;
            $paths[] = $path;
        }

        $result = $this->dominant($tally, $paths);

        return $result instanceof ApproachResult ? [$result] : [];
    }
}
