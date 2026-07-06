<?php

declare(strict_types=1);

namespace Laravel\Roster\Enums;

enum PackageSource: string
{
    case COMPOSER = 'composer';
    case NPM = 'npm';
}
