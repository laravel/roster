<?php

declare(strict_types=1);

namespace Laravel\Roster\Enums;

enum Stack: string
{
    case INERTIA_REACT = 'inertia-react';
    case INERTIA_VUE = 'inertia-vue';
    case INERTIA_SVELTE = 'inertia-svelte';
    case LIVEWIRE = 'livewire';
    case API = 'api';
    case BLADE = 'blade';
}
