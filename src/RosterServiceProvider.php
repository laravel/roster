<?php

declare(strict_types=1);

namespace Laravel\Roster;

use Illuminate\Support\ServiceProvider;

class RosterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProjectManager::class, fn (): ProjectManager => new ProjectManager);

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
