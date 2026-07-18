<?php

declare(strict_types=1);

namespace Laravel\Roster\Enums;

enum PackageSource: string
{
    case Composer = 'composer';
    case Npm = 'npm';
}
