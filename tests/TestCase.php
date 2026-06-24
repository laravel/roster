<?php

declare(strict_types=1);

namespace Tests;

use Laravel\Roster\RosterServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function defineEnvironment($app) {}

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [RosterServiceProvider::class];
    }
}
