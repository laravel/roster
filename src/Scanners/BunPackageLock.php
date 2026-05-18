<?php

namespace Laravel\Roster\Scanners;

use Illuminate\Support\Facades\Log;
use Laravel\Roster\PackageCollection;

class BunPackageLock extends BasePackageScanner
{
    protected function lockFile(): string
    {
        return 'bun.lock';
    }

    public function scan(): PackageCollection
    {
        $packages = new PackageCollection;
        $lockFilePath = $this->lockFilePath();

        $contents = $this->validateFile($lockFilePath);
        if ($contents === null) {
            return $packages;
        }

        /** @var string $contents */
        $contents = preg_replace('/,\s*([]}])/m', '$1', $contents);
        $json = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($json)) {
            Log::warning('Failed to decode bun.lock: '.$lockFilePath);

            return $packages;
        }

        /** @var array<string, array<string, mixed>> $json */
        if (! isset($json['workspaces']['']) || ! isset($json['packages'])) {
            Log::warning('Malformed bun.lock');

            return $packages;
        }

        /** @var array<string, mixed> $workspace */
        $workspace = $json['workspaces'][''];

        /** @var array<string, string> $dependencies */
        $dependencies = $workspace['dependencies'] ?? [];
        /** @var array<string, string> $devDependencies */
        $devDependencies = $workspace['devDependencies'] ?? [];

        $this->processDependencies($dependencies, $packages, false);
        $this->processDependencies($devDependencies, $packages, true);

        return $packages;
    }
}
