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
            Approach::NOTIFICATION_NOTIFY->value => 0,
            Approach::NOTIFICATION_FACADE->value => 0,
        ];

        $paths = [];

        foreach ($files->php() as $path) {
            $contents = $files->contents($path);

            $notify = (int) preg_match_all('/->notify\(\s*new\s/', $contents);
            $facade = (int) preg_match_all('/\bNotification::send(?:Now)?\s*\(/', $contents);

            if ($notify === 0 && $facade === 0) {
                continue;
            }

            $tally[Approach::NOTIFICATION_NOTIFY->value] += $notify;
            $tally[Approach::NOTIFICATION_FACADE->value] += $facade;
            $paths[] = $path;
        }

        return $this->dominant($tally, $paths);
    }
}
