<?php

namespace Laravel\Roster;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class RosterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Registry::class, fn (): \Laravel\Roster\Registry => new Registry);

        $this->app->singleton(RosterManager::class, fn (Application $app): \Laravel\Roster\RosterManager => new RosterManager(
            $app->make(Registry::class),
        ));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\ScanCommand::class,
            ]);
        }
    }
}
