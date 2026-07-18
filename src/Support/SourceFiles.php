<?php

declare(strict_types=1);

namespace Laravel\Roster\Support;

use Illuminate\Support\Str;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use UnexpectedValueException;

class SourceFiles
{
    protected string $basePath;

    /** @var list<string>|null */
    protected ?array $roots = null;

    /** @var array<string, list<string>> */
    protected array $filesByRoot = [];

    /** @var array<string, list<string>> */
    protected array $filesBySubpath = [];

    public function __construct(string $basePath)
    {
        $this->basePath = Str::finish($basePath, DIRECTORY_SEPARATOR);
    }

    /**
     * @return list<string>
     */
    public function php(?string $subpath = null): array
    {
        $subpath = trim(str_replace('\\', '/', (string) $subpath), '/');

        return $this->filesBySubpath[$subpath] ??= $this->resolvePhp($subpath === '' ? null : $subpath);
    }

    /**
     * @return list<string>
     */
    protected function resolvePhp(?string $subpath): array
    {
        $pattern = $subpath === null
            ? null
            : '#(^|/)'.preg_quote($subpath, '#').'/#';

        $files = [];

        foreach ($this->roots() as $root) {
            foreach ($this->phpFilesIn($root) as $file) {
                $relative = str_replace('\\', '/', substr($file, strlen($root) + 1));

                if ($pattern !== null && preg_match($pattern, $relative) !== 1) {
                    continue;
                }

                $files[realpath($file) ?: $file] = $file;
            }
        }

        $files = array_values($files);
        sort($files);

        return $files;
    }

    public function contents(string $path): string
    {
        return is_file($path) ? ((string) @file_get_contents($path)) : '';
    }

    /**
     * @return list<string>
     */
    public function roots(): array
    {
        return $this->roots ??= $this->resolveRoots();
    }

    /**
     * @return list<string>
     */
    protected function resolveRoots(): array
    {
        $existing = [];

        foreach ([...$this->psr4Roots(), $this->basePath.'app'] as $root) {
            $real = realpath(rtrim($root, '/\\'));

            if ($real === false) {
                continue;
            }

            if (! is_dir($real)) {
                continue;
            }

            $existing[$real] = $real;
        }

        return array_values($existing);
    }

    /**
     * @return list<string>
     */
    protected function psr4Roots(): array
    {
        $path = $this->basePath.'composer.json';

        if (! is_file($path)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data)) {
            return [];
        }

        $autoload = $data['autoload'] ?? null;
        $psr4 = is_array($autoload) && is_array($autoload['psr-4'] ?? null) ? $autoload['psr-4'] : [];

        $roots = [];

        foreach ($psr4 as $paths) {
            foreach (is_array($paths) ? $paths : [$paths] as $relative) {
                if (is_string($relative) && $relative !== '') {
                    $roots[] = $this->basePath.str_replace('/', DIRECTORY_SEPARATOR, trim($relative, '/'));
                }
            }
        }

        return $roots;
    }

    /**
     * @return list<string>
     */
    protected function phpFilesIn(string $root): array
    {
        return $this->filesByRoot[$root] ??= $this->enumeratePhpFiles($root);
    }

    /**
     * @return list<string>
     */
    protected function enumeratePhpFiles(string $root): array
    {
        $files = [];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveCallbackFilterIterator(
                    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
                    fn (SplFileInfo $file): bool => ! $file->isDir()
                        || (! str_starts_with($file->getFilename(), '.') && ! in_array($file->getFilename(), ['vendor', 'node_modules'], true)),
                ),
            );

            foreach ($iterator as $file) {
                if ($file instanceof SplFileInfo && $file->isFile() && strtolower($file->getExtension()) === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        } catch (UnexpectedValueException) {
            //
        }

        sort($files);

        return $files;
    }
}
