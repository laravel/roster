<?php

namespace Laravel\Roster\Enums;

enum Packages: string
{
    // Compound
    case INERTIA = 'inertia';
    case WAYFINDER = 'wayfinder';

    // BACKEND
    case FILAMENT = 'filament';
    case FOLIO = 'folio';
    case FLUXUI_FREE = 'flux_free';
    case FLUXUI_PRO = 'flux_pro';
    case INERTIA_LARAVEL = 'inertia-laravel';
    case LARASTAN = 'larastan';
    case LARAVEL = 'laravel';
    case LIVEWIRE = 'livewire';
    case NIGHTWATCH = 'nightwatch';
    case NOVA = 'nova';
    case OCTANE = 'octane';
    case PENNANT = 'pennant';
    case PEST = 'pest';
    case PHPUNIT = 'phpunit';
    case PINT = 'pint';
    case PROMPTS = 'prompts';
    case RECTOR = 'rector';
    case REVERB = 'reverb';
    case SCOUT = 'scout';
    case STATAMIC = 'statamic';
    case VOLT = 'volt';
    case WAYFINDER_LARAVEL = 'wayfinder_laravel';
    case ZIGGY = 'ziggy';

    // NPM
    case ALPINEJS = 'alpinejs';
    case ECHO = 'laravel-echo';
    case INERTIA_REACT = 'inertia-react';
    case INERTIA_SVELTE = 'inertia-svelte';
    case INERTIA_VUE = 'inertia-vue';
    case REACT = 'react';
    case TAILWINDCSS = 'tailwindcss';
    case VUE = 'vue';
    case WAYFINDER_VITE = 'wayfinder_vite';
}
