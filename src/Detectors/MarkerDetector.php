<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use BackedEnum;
use Laravel\Roster\Support\SystemProbe;

/**
 * @template TEnum of BackedEnum
 */
abstract class MarkerDetector
{
    /**
     * Project filesystem markers keyed by enum value. Markers may include
     * glob characters (e.g. `*.sublime-project`).
     *
     * @return array<string, list<string>>
     */
    abstract protected static function projectMarkers(): array;

    /**
     * System binaries keyed by enum value.
     *
     * @return array<string, list<string>>
     */
    abstract protected static function systemBinaries(): array;

    /**
     * @return TEnum
     */
    abstract protected static function fromValue(string $value): BackedEnum;

    /**
     * @return list<TEnum>
     */
    public static function configured(string $basePath): array
    {
        $configured = [];

        foreach (static::projectMarkers() as $value => $markers) {
            foreach ($markers as $marker) {
                if (self::markerMatches($basePath, $marker)) {
                    $configured[] = static::fromValue((string) $value);

                    break;
                }
            }
        }

        return $configured;
    }

    /**
     * @return list<TEnum>
     */
    public static function installed(): array
    {
        $installed = [];

        foreach (static::systemBinaries() as $value => $binaries) {
            foreach ($binaries as $binary) {
                if (SystemProbe::commandExists($binary)) {
                    $installed[] = static::fromValue((string) $value);

                    break;
                }
            }
        }

        return $installed;
    }

    private static function markerMatches(string $basePath, string $marker): bool
    {
        $path = $basePath.str_replace('/', DIRECTORY_SEPARATOR, $marker);

        if (str_contains($marker, '*')) {
            $matches = glob($path);

            return is_array($matches) && $matches !== [];
        }

        return SystemProbe::pathExists($path);
    }
}
