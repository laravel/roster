<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use BackedEnum;

/**
 * @template TEnum of BackedEnum
 */
abstract class MarkerDetector
{
    /** @var class-string<TEnum> */
    protected const ENUM = BackedEnum::class;

    /**
     * @return array<string, list<string>>
     */
    abstract protected static function projectMarkers(): array;

    /**
     * @return list<TEnum>
     */
    public static function detect(string $basePath): array
    {
        $detected = [];

        foreach (static::projectMarkers() as $value => $markers) {
            foreach ($markers as $marker) {
                if (self::markerMatches($basePath, $marker)) {
                    $detected[] = static::ENUM::from((string) $value);

                    break;
                }
            }
        }

        return $detected;
    }

    /**
     * @return list<string>
     */
    public static function markerPaths(): array
    {
        $paths = [];

        foreach (static::projectMarkers() as $markers) {
            foreach ($markers as $marker) {
                $paths[] = $marker;
            }
        }

        return array_values(array_unique($paths));
    }

    public static function markerMatches(string $basePath, string $marker): bool
    {
        $relative = str_replace('/', DIRECTORY_SEPARATOR, $marker);

        if (! str_contains($marker, '*')) {
            return file_exists($basePath.$relative);
        }

        $directory = dirname($relative) === '.'
            ? rtrim($basePath, DIRECTORY_SEPARATOR)
            : $basePath.dirname($relative);
        $pattern = basename($relative);

        if (! is_dir($directory)) {
            return false;
        }

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..' && fnmatch($pattern, $entry)) {
                return true;
            }
        }

        return false;
    }
}
