<?php

namespace Laravel\Roster\Enums;

enum StarterKit: string
{
    case REACT = 'react';
    case REACT_WORKOS = 'react-workos';
    case VUE = 'vue';
    case VUE_WORKOS = 'vue-workos';
    case SVELTE = 'svelte';
    case SVELTE_WORKOS = 'svelte-workos';
    case LIVEWIRE = 'livewire';
    case LIVEWIRE_WORKOS = 'livewire-workos';
}
