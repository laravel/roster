<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class ValidationStyle extends Convention
{
    protected function result(string $basePath, SourceFiles $files): ?ApproachResult
    {
        $tally = [
            Approach::VALIDATION_INLINE->value => 0,
            Approach::VALIDATION_FORM_REQUEST->value => 0,
        ];

        $paths = [];
        $formRequests = [];

        foreach ($files->php('Http/Requests') as $path) {
            if (str_contains($files->contents($path), 'function rules')) {
                $formRequests[$path] = true;
                $tally[Approach::VALIDATION_FORM_REQUEST->value]++;
                $paths[] = $path;
            }
        }

        foreach ($files->php() as $path) {
            if (isset($formRequests[$path])) {
                continue;
            }

            $contents = $files->contents($path);

            $inline = preg_match('/(?:\$request|\$this)->validate(?:WithBag)?\s*\(/', $contents) === 1
                || preg_match('/Validator::make\s*\(/', $contents) === 1;

            if (! $inline) {
                continue;
            }

            $tally[Approach::VALIDATION_INLINE->value]++;
            $paths[] = $path;
        }

        return $this->dominant($tally, $paths);
    }
}
