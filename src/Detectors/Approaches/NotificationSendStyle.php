<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class NotificationSendStyle extends Convention
{
    /**
     * @return list<ApproachResult>
     */
    public function detect(string $basePath, SourceFiles $files): array
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

        $result = $this->dominant($tally, $paths);

        return $result instanceof ApproachResult ? [$result] : [];
    }
}
