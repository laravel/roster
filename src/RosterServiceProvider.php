<?php

namespace Laravel\Roster;

use Illuminate\Support\ServiceProvider;

class RosterServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerCommands();
    }

    /**
     * Register the package's commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\ScanCommand::class,
            ]);
        }
    }
}
