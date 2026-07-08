<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use Laravel\Roster\Ecosystems\JsEcosystem;
use Laravel\Roster\Enums\Frontend;

class FrontendDetector
{
    /** @var array<string, list<string>> Marker packages per frontend (any direct match counts) */
    private const MARKERS = [
        'vue' => ['vue', '@vitejs/plugin-vue', '@inertiajs/vue3'],
        'react' => ['react', 'react-dom', '@vitejs/plugin-react', '@inertiajs/react'],
        'svelte' => ['svelte', '@sveltejs/kit', '@sveltejs/vite-plugin-svelte', '@inertiajs/svelte'],
    ];

    /**
     * @return list<Frontend>
     */
    public static function detect(JsEcosystem $js): array
    {
        $found = [];

        foreach (self::MARKERS as $value => $markers) {
            if ($js->usesDirect($markers)) {
                $found[] = Frontend::from($value);
            }
        }

        return $found;
    }
}
