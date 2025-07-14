<?php

namespace Laravel\Using;

use Illuminate\Support\ServiceProvider;

class UsingServiceProvider extends ServiceProvider
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
                Console\CheckCommand::class,
            ]);
        }
    }
}
