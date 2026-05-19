<?php

declare(strict_types=1);

namespace Laravel\Roster;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class RosterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Registry::class, fn (): Registry => new Registry);

        $this->app->singleton(ProjectManager::class, fn (Application $app): ProjectManager => new ProjectManager(
            $app->make(Registry::class),
        ));

        $this->app->singleton(SystemManager::class, fn (): SystemManager => new SystemManager);
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
