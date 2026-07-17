<?php

declare(strict_types=1);

namespace Laravel\Roster\Scanners;

use Laravel\Roster\PackageCollection;

class BunPackageLock extends JsPackageScanner
{
    public function scan(): PackageCollection
    {
        $packages = new PackageCollection;
        $lockFilePath = $this->path.'bun.lock';

        $contents = $this->readContents($lockFilePath, 'bun.lock');

        if ($contents === null) {
            $this->failed = true;

            return $packages;
        }

        $json = $this->decodeLockfile($contents);

        if ($json === null) {
            $this->warn('Failed to decode bun.lock: '.$lockFilePath);

            $this->failed = true;

            return $packages;
        }

        if (! is_array($json['packages'] ?? null)) {
            $this->warn('Malformed bun.lock (missing "packages" key): '.$lockFilePath);

            $this->failed = true;

            return $packages;
        }

        /** @var array<string, array{version: string, topLevel: bool}> $byName */
        $byName = [];

        foreach ($json['packages'] as $key => $entry) {
            $key = (string) $key;

            $topLevel = ! str_contains($key, '/')
                || (str_starts_with($key, '@') && substr_count($key, '/') === 1);

            $name = $topLevel ? $key : ($this->extractName($entry) ?? $key);

            if ($name === '') {
                continue;
            }

            if (isset($byName[$name]) && ! ($topLevel && ! $byName[$name]['topLevel'])) {
                continue;
            }

            $byName[$name] = ['version' => $this->extractVersion($entry), 'topLevel' => $topLevel];
        }

        $devSet = array_flip($this->workspaceDevNames($json));

        $prod = [];
        $dev = [];

        foreach ($byName as $name => $entry) {
            if ($entry['topLevel'] && isset($devSet[$name])) {
                $dev[$name] = $entry['version'];
            } else {
                $prod[$name] = $entry['version'];
            }
        }

        $this->processDependencies($prod, $packages, false);
        $this->processDependencies($dev, $packages, true);

        return $packages;
    }

    /**
     * @param  array<string, mixed>  $json
     * @return list<string>
     */
    private function workspaceDevNames(array $json): array
    {
        $workspaces = $json['workspaces'] ?? null;
        $root = is_array($workspaces) ? ($workspaces[''] ?? null) : null;
        $dev = is_array($root) ? ($root['devDependencies'] ?? null) : null;

        return is_array($dev) ? array_map(strval(...), array_keys($dev)) : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function decodeLockfile(string $contents): ?array
    {
        $json = json_decode($contents, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            /** @var array<string, mixed> $json */
            return $json;
        }

        $sanitized = preg_replace('/,[ \t]*(\r?\n\s*)([]}])/', '$1$2', $contents) ?? $contents;

        $json = json_decode($sanitized, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            /** @var array<string, mixed> $json */
            return $json;
        }

        return null;
    }

    private function extractName(mixed $entry): ?string
    {
        if (! is_array($entry) || ! isset($entry[0]) || ! is_string($entry[0])) {
            return null;
        }

        $position = strrpos($entry[0], '@');

        return $position === false || $position === 0 ? null : substr($entry[0], 0, $position);
    }

    private function extractVersion(mixed $entry): string
    {
        if (is_array($entry) && isset($entry[0]) && is_string($entry[0])) {
            $position = strrpos($entry[0], '@');

            return $position === false ? $entry[0] : substr($entry[0], $position + 1);
        }

        if (is_string($entry)) {
            return $entry;
        }

        return '';
    }
}
