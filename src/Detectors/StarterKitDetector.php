<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use Illuminate\Support\Arr;
use Laravel\Roster\Scanners\Concerns\ParsesManifests;

class StarterKitDetector
{
    use ParsesManifests;

    public static function detect(string $basePath): ?string
    {
        $composer = self::readJsonFile($basePath.'composer.json') ?? [];
        $starterKit = Arr::get($composer, 'extra.laravel.starter-kit');

        return is_string($starterKit) && $starterKit !== '' ? $starterKit : null;
    }
}
