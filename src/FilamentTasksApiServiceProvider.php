<?php

namespace Lightworx\FilamentTasksApi;

use Illuminate\Support\ServiceProvider;

class FilamentTasksApiServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/filament-tasks-api.php',
            'filament-tasks-api'
        );
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/filament-tasks-api.php'
                => config_path('filament-tasks-api.php'),
        ], 'filament-tasks-api-config');
    }
}