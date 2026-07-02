<?php

declare(strict_types=1);

namespace Laravel\Roster\Support;

use Illuminate\Support\Str;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use UnexpectedValueException;

/**
 * Enumerates the application's own PHP source files from its composer.json
 * PSR-4 autoload roots unioned with app/, deduped by real path.
 */
class SourceFiles
{
    protected string $basePath;

    /** @var list<string>|null */
    protected ?array $roots = null;

    /** @var array<string, string> */
    protected array $contents = [];

    /** @var array<string, list<string>> */
    protected array $filesByRoot = [];

    public function __construct(string $basePath)
    {
        $this->basePath = Str::finish($basePath, DIRECTORY_SEPARATOR);
    }

    /**
     * PHP files across every source root. When a subpath is given it matches
     * anywhere beneath the root (`(^|/)Models/`), so modular layouts like
     * `src/Domain/Orders/Models/Order.php` are sampled — not just `app/Models/`.
     * A root that is itself the target directory (e.g. a PSR-4 mapping straight
     * into `src/Models`) is not matched — the subpath must appear beneath it.
     *
     * @return list<string>
     */
    public function php(?string $subpath = null): array
    {
        $pattern = $subpath === null
            ? null
            : '#(^|/)'.preg_quote(trim(str_replace('\\', '/', $subpath), '/'), '#').'/#';

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

    /**
     * Cheap containment check that does not populate the content cache — used
     * to skip files before a heavier parse without retaining every scanned
     * file's bytes.
     */
    public function contains(string $path, string $needle): bool
    {
        if (array_key_exists($path, $this->contents)) {
            return stripos($this->contents[$path], $needle) !== false;
        }

        $contents = $this->read($path);

        return $contents !== false && stripos($contents, $needle) !== false;
    }

    public function contents(string $path): string
    {
        if (! array_key_exists($path, $this->contents)) {
            $contents = $this->read($path);
            $this->contents[$path] = $contents === false ? '' : $contents;
        }

        return $this->contents[$path];
    }

    /**
     * @return list<string>
     */
    public function roots(): array
    {
        return $this->roots ??= $this->resolveRoots();
    }

    protected function read(string $path): string|false
    {
        return is_file($path) ? @file_get_contents($path) : false;
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
     * Walks a root for PHP files, pruning dependency and hidden directories so
     * a PSR-4 mapping that resolves to the project root (e.g. `"App\\": "."`)
     * cannot let vendor code vote on the application's conventions.
     *
     * @return list<string>
     */
    protected function enumeratePhpFiles(string $root): array
    {
        $files = [];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveCallbackFilterIterator(
                    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
                    fn (SplFileInfo $file): bool => ! ($file->isDir() && (
                        str_starts_with($file->getFilename(), '.')
                        || in_array($file->getFilename(), ['vendor', 'node_modules'], true)
                    )),
                ),
            );

            foreach ($iterator as $file) {
                if ($file instanceof SplFileInfo && $file->isFile() && strtolower($file->getExtension()) === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        } catch (UnexpectedValueException) {
            // Keep whatever was collected before the unreadable entry.
        }

        sort($files);

        return $files;
    }
}
