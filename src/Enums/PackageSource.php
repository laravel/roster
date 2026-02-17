<?php

namespace Laravel\Roster\Enums;

enum PackageSource: string
{
    case COMPOSER = 'composer';
    case NPM = 'npm';
}
