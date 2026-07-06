<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class AuthRetrievalStyle extends Convention
{
    protected function result(string $basePath, SourceFiles $files): ?ApproachResult
    {
        $tally = [
            Approach::AUTH_FACADE->value => 0,
            Approach::AUTH_REQUEST->value => 0,
            Approach::AUTH_HELPER->value => 0,
        ];

        $paths = [];

        foreach ($files->php() as $path) {
            $contents = $files->contents($path);

            $facade = (int) preg_match_all('/\bAuth::(?:user|id|check|guest)\s*\(/', $contents);
            $request = (int) preg_match_all('/\$request->user\(\)/', $contents);
            $helper = (int) preg_match_all('/\bauth\(\)->(?:user|id|check|guest)\s*\(/', $contents);

            if ($facade === 0 && $request === 0 && $helper === 0) {
                continue;
            }

            $tally[Approach::AUTH_FACADE->value] += $facade;
            $tally[Approach::AUTH_REQUEST->value] += $request;
            $tally[Approach::AUTH_HELPER->value] += $helper;
            $paths[] = $path;
        }

        return $this->dominant($tally, $paths);
    }
}
