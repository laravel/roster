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
        $tally = [
            Approach::AUTHORIZATION_GATE->value => 0,
            Approach::AUTHORIZATION_USER_CAN->value => 0,
            Approach::AUTHORIZATION_ATTRIBUTE->value => 0,
            Approach::AUTHORIZATION_TRAIT->value => 0,
        ];

        $paths = [];

        foreach ($files->php('Http/Controllers') as $path) {
            $contents = $files->contents($path);

            $gate = (int) preg_match_all('/\bGate::(?:authorize|allows|denies|any|none|check|inspect)\s*\(/', $contents);
            $userCan = (int) preg_match_all('/->user\(\)->(?:can|cannot)\s*\(/', $contents);
            $attribute = (int) preg_match_all('/#\[\s*Authorize\b/', $contents);
            $trait = (int) preg_match_all('/\$this->authorize\s*\(/', $contents);

            if ($gate === 0 && $userCan === 0 && $attribute === 0 && $trait === 0) {
                continue;
            }

            $tally[Approach::AUTHORIZATION_GATE->value] += $gate;
            $tally[Approach::AUTHORIZATION_USER_CAN->value] += $userCan;
            $tally[Approach::AUTHORIZATION_ATTRIBUTE->value] += $attribute;
            $tally[Approach::AUTHORIZATION_TRAIT->value] += $trait;
            $paths[] = $path;
        }

        return $this->dominant($tally, $paths);
    }
}
