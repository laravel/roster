<?php

namespace Laravel\Roster\Support;

class SystemProbe
{
    /** @var array<string, bool> */
    protected static array $cache = [];

    public static function commandExists(string $binary): bool
    {
        if (array_key_exists($binary, self::$cache)) {
            return self::$cache[$binary];
        }

        if (! function_exists('proc_open')) {
            return self::$cache[$binary] = false;
        }

        $isWindows = DIRECTORY_SEPARATOR === '\\';
        $cmd = $isWindows ? 'where '.escapeshellarg($binary) : 'command -v '.escapeshellarg($binary);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = @proc_open($cmd, $descriptors, $pipes);
        if (! is_resource($proc)) {
            return self::$cache[$binary] = false;
        }

        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        $exit = proc_close($proc);

        return self::$cache[$binary] = ($exit === 0);
    }

    public static function pathExists(string $path): bool
    {
        return $path !== '' && (file_exists($path) || is_dir($path));
    }

    public static function resetCache(): void
    {
        self::$cache = [];
    }
}
