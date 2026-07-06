<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use BackedEnum;

/**
 * @template TEnum of BackedEnum
 */
abstract class MarkerDetector
{
    /**
     * @return array<string, list<string>>
     */
    abstract protected static function projectMarkers(): array;

    /**
     * @return TEnum
     */
    abstract protected static function fromValue(string $value): BackedEnum;

    /**
     * @return list<TEnum>
     */
    public static function detect(string $basePath): array
    {
        $detected = [];

        foreach (static::projectMarkers() as $value => $markers) {
            foreach ($markers as $marker) {
                if (self::markerMatches($basePath, $marker)) {
                    $detected[] = static::fromValue((string) $value);

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
        $path = $basePath.str_replace('/', DIRECTORY_SEPARATOR, $marker);

        if (str_contains($marker, '*')) {
            $matches = glob($path);

            return is_array($matches) && $matches !== [];
        }

        return file_exists($path);
    }
}
