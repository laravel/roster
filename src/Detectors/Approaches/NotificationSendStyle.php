<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class NotificationSendStyle extends Convention
{
    protected function result(string $basePath, SourceFiles $files): ?ApproachResult
    {
        $tally = [
            Approach::NotificationNotify->value => 0,
            Approach::NotificationFacade->value => 0,
        ];

        $paths = [];

        foreach ($files->php() as $path) {
            $contents = $files->contents($path);

            $winner = $this->fileVote([
                Approach::NotificationNotify->value => (int) preg_match_all('/->notify\(\s*new\s/', $contents),
                Approach::NotificationFacade->value => (int) preg_match_all('/\bNotification::send(?:Now)?\s*\(/', $contents),
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
