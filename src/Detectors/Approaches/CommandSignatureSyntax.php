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
            Approach::CommandAttributeSyntax->value => 0,
            Approach::CommandPropertySyntax->value => 0,
        ];

        $paths = [];

        foreach ($files->php('Commands') as $path) {
            $contents = $files->contents($path);

            $winner = $this->fileVote([
                Approach::CommandAttributeSyntax->value => (int) preg_match_all('/#\[\s*(?:Signature|Description)\b/', $contents),
                Approach::CommandPropertySyntax->value => (int) preg_match_all('/protected\s+\$(?:signature|description)\b\s*=/', $contents),
            ]);

            if ($winner === null) {
                continue;
            }

            $tally[$winner]++;
            $paths[] = $path;
        }

        return $this->dominant($tally, $paths);
    }
}
