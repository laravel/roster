<?php

namespace Tests;

use Laravel\Roster\Roster;
use Laravel\Roster\RosterServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function defineEnvironment($app) {}

    protected function setUp(): void
    {
        parent::setUp();

        Roster::$authUsing = function () {
            return true;
        };
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Roster::$authUsing = null;
    }

    protected function getPackageProviders($app)
    {
        return [RosterServiceProvider::class];
    }
}
