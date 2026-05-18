<?php

namespace Laravel\Roster\Detectors;

use Laravel\Roster\Ecosystems\PhpEcosystem;
use Laravel\Roster\Enums\StarterKit;
use Laravel\Roster\Support\EnumSet;
use Laravel\Roster\Support\SystemProbe;

class StarterKitDetector
{
    public function __construct(protected string $basePath) {}

    /**
     * @return EnumSet<StarterKit>
     */
    public function detect(PhpEcosystem $php): EnumSet
    {
        if (! $this->pathExists('routes/settings.php')) {
            return new EnumSet([]);
        }

        $variant = $this->detectVariant();
        if ($variant === null) {
            return new EnumSet([]);
        }

        $kit = $this->hasWorkOs($php) ? $variant['workos'] : $variant['base'];

        return new EnumSet([$kit]);
    }

    /**
     * @return array{base: StarterKit, workos: StarterKit}|null
     */
    private function detectVariant(): ?array
    {
        if ($this->pathExists('resources/js/app.tsx') && $this->pathExists('resources/js/pages')) {
            return ['base' => StarterKit::REACT, 'workos' => StarterKit::REACT_WORKOS];
        }

        if ($this->pathExists('resources/js/app.ts')) {
            // Cheap dir check first; the Svelte fallback recurses the pages tree.
            if ($this->pathExists('resources/js/composables')) {
                return ['base' => StarterKit::VUE, 'workos' => StarterKit::VUE_WORKOS];
            }

            if ($this->dirHasSvelteFiles('resources/js/pages')) {
                return ['base' => StarterKit::SVELTE, 'workos' => StarterKit::SVELTE_WORKOS];
            }
        }

        if ($this->pathExists('resources/views/flux') && $this->pathExists('app/Livewire/Actions')) {
            return ['base' => StarterKit::LIVEWIRE, 'workos' => StarterKit::LIVEWIRE_WORKOS];
        }

        return null;
    }

    private function hasWorkOs(PhpEcosystem $php): bool
    {
        if (! $php->uses('workos/workos-php')) {
            return false;
        }

        $contents = @file_get_contents($this->absolutePath('config/services.php'));

        return is_string($contents) && stripos($contents, 'workos') !== false;
    }

    private function pathExists(string $relative): bool
    {
        return SystemProbe::pathExists($this->absolutePath($relative));
    }

    private function absolutePath(string $relative): string
    {
        return $this->basePath.str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    private function dirHasSvelteFiles(string $relative): bool
    {
        $dir = $this->absolutePath($relative);

        if (! is_dir($dir)) {
            return false;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file instanceof \SplFileInfo) {
                continue;
            }
            if ($file->isFile() && str_ends_with(strtolower($file->getFilename()), '.svelte')) {
                return true;
            }
        }

        return false;
    }
}
