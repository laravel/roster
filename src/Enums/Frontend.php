<?php

namespace Laravel\Roster\Enums;

enum Frontend: string
{
    case VUE = 'vue';
    case REACT = 'react';
    case SVELTE = 'svelte';
}
