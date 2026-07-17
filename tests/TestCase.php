<?php

declare(strict_types=1);

namespace Tests;

use Laravel\Roster\RosterServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [RosterServiceProvider::class];
    }
}
