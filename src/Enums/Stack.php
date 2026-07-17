<?php

declare(strict_types=1);

namespace Laravel\Roster\Enums;

enum Stack: string
{
    case InertiaReact = 'inertia-react';
    case InertiaVue = 'inertia-vue';
    case InertiaSvelte = 'inertia-svelte';
    case Livewire = 'livewire';
    case Api = 'api';
    case Blade = 'blade';
}
