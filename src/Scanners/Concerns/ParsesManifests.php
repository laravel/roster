<?php

declare(strict_types=1);

namespace Laravel\Roster\Scanners\Concerns;

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
     * @return array<string, array{constraint: string, isDev: bool}>
     */
    protected static function collectManifestDeps(array $manifest, string $prodKey, string $devKey): array
    {
        return [
            ...self::collectDeps($manifest[$prodKey] ?? null, false),
            ...self::collectDeps($manifest[$devKey] ?? null, true),
        ];
    }

    protected static function normalizeVersion(string $version): string
    {
        return preg_replace('/[^0-9.]/', '', $version) ?? '';
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
            if (! is_string($name)) {
                continue;
            }

            if (! is_scalar($constraint)) {
                continue;
            }

            $collected[$name] = ['constraint' => (string) $constraint, 'isDev' => $isDev];
        }

        return $collected;
    }
}
