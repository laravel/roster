<?php

declare(strict_types=1);

namespace Laravel\Roster\Enums;

enum BrowserTestFramework: string
{
    case Dusk = 'dusk';
    case PestBrowser = 'pest-browser';
    case Playwright = 'playwright';
    case Cypress = 'cypress';
}
