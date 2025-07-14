<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use Laravel\Using\Using;
use Laravel\Using\UsingServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function defineEnvironment($app)
    {
        Artisan::call('vendor:publish', ['--tag' => 'using-assets']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Using::$authUsing = function () {
            return true;
        };
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Using::$authUsing = null;
    }

    protected function getPackageProviders($app)
    {
        return [UsingServiceProvider::class];
    }
}
