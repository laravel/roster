<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class HttpClientErrorStyle extends Convention
{
    /**
     * @return list<ApproachResult>
     */
    public function detect(string $basePath, SourceFiles $files): array
    {
        $tally = [
            Approach::HTTP_CLIENT_THROW->value => 0,
            Approach::HTTP_CLIENT_STATUS_CHECK->value => 0,
        ];

        $paths = [];

        foreach ($files->php() as $path) {
            $contents = $files->contents($path);

            if (! str_contains($contents, 'Http::')) {
                continue;
            }

            $throw = (int) preg_match_all('/->throw(?:If|Unless|IfStatus|UnlessStatus|IfServerError|IfClientError)?\s*\(/', $contents);
            $check = (int) preg_match_all('/->(?:successful|failed|clientError|serverError)\s*\(\s*\)/', $contents);

            if ($throw === 0 && $check === 0) {
                continue;
            }

            $tally[Approach::HTTP_CLIENT_THROW->value] += $throw;
            $tally[Approach::HTTP_CLIENT_STATUS_CHECK->value] += $check;
            $paths[] = $path;
        }

        $result = $this->dominant($tally, $paths);

        return $result instanceof ApproachResult ? [$result] : [];
    }
}
