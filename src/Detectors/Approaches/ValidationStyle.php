<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class ValidationStyle extends Convention
{
    /**
     * @return list<ApproachResult>
     */
    public function detect(string $basePath, SourceFiles $files): array
    {
        $tally = [
            Approach::VALIDATION_INLINE->value => 0,
            Approach::VALIDATION_FORM_REQUEST->value => 0,
        ];

        $paths = [];

        foreach ($files->php('Http/Requests') as $path) {
            if (str_contains($files->contents($path), 'function rules')) {
                $tally[Approach::VALIDATION_FORM_REQUEST->value]++;
                $paths[] = $path;
            }
        }

        foreach ($files->php() as $path) {
            $contents = $files->contents($path);

            $inline = (int) preg_match_all('/(?:\$request|\$this)->validate(?:WithBag)?\s*\(/', $contents)
                + (int) preg_match_all('/Validator::make\s*\(/', $contents);

            if ($inline === 0) {
                continue;
            }

            $tally[Approach::VALIDATION_INLINE->value] += $inline;
            $paths[] = $path;
        }

        $result = $this->dominant($tally, $paths);

        return $result instanceof ApproachResult ? [$result] : [];
    }
}
