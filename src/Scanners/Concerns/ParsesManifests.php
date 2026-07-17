<?php

declare(strict_types=1);

namespace Laravel\Roster\Scanners\Concerns;

use Composer\Semver\VersionParser;
use UnexpectedValueException;

trait ParsesManifests
{
    /**
     * @return array<string, mixed>|null
     */
    protected static function readJsonFile(string $path): ?array
    {
        if (! file_exists($path) || ! is_readable($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $json = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($json)) {
            return null;
        }

        /** @var array<string, mixed> $json */
        return $json;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<string, bool>  $sections
     * @return array<string, array{constraint: string, isDev: bool}>
     */
    protected static function collectManifestDeps(array $manifest, array $sections): array
    {
        $collected = [];

        foreach ($sections as $section => $isDev) {
            $collected = array_replace($collected, self::collectDeps($manifest[$section] ?? null, $isDev));
        }

        return $collected;
    }

    protected static function normalizeVersion(string $version): string
    {
        if (preg_match('/\d+(?:\.\d+)*(?:-[0-9A-Za-z.]+)?/', $version, $matches) !== 1) {
            return '';
        }

        $normalized = $matches[0];

        if (str_contains($normalized, '-')) {
            try {
                (new VersionParser)->normalize($normalized);
            } catch (UnexpectedValueException) {
                return explode('-', $normalized, 2)[0];
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, array{constraint: string, isDev: bool}>
     */
    private static function collectDeps(mixed $deps, bool $isDev): array
    {
        if (! is_array($deps)) {
            return [];
        }

        $collected = [];

        foreach ($deps as $name => $constraint) {
            if (! is_scalar($constraint)) {
                continue;
            }

            $collected[(string) $name] = ['constraint' => (string) $constraint, 'isDev' => $isDev];
        }

        return $collected;
    }
}
