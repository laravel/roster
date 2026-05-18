<?php

namespace Laravel\Roster\Enums;

enum BrowserTestFramework: string
{
    case DUSK = 'dusk';
    case PEST_BROWSER = 'pest-browser';
    case PLAYWRIGHT = 'playwright';
    case CYPRESS = 'cypress';
}
