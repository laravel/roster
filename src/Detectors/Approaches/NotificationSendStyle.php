<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class NotificationSendStyle extends Convention
{
    protected function result(SourceFiles $files): ?ApproachResult
    {
        return $this->electByFile($files, null, fn (string $contents): array => [
            Approach::NotificationNotify->value => (int) preg_match_all('/->notify\s*\(/', $contents),
            Approach::NotificationFacade->value => (int) preg_match_all('/\bNotification::send(?:Now)?\s*\(/', $contents),
        ]);
    }
}
