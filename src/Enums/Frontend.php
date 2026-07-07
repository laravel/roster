<?php

declare(strict_types=1);

namespace Laravel\Roster\Enums;

enum Frontend: string
{
    case Vue = 'vue';
    case React = 'react';
    case Svelte = 'svelte';
}
