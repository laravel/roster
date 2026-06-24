<?php

declare(strict_types=1);

namespace Laravel\Roster\Enums;

enum Approach: string
{
    case ACTION = 'action';
    case DDD = 'ddd';
    case MODULAR = 'modular';
}
