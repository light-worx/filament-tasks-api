<?php

namespace Lightworx\FilamentTasksApi;

use Illuminate\Support\ServiceProvider;

class FilamentTasksApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/filament-tasks-api.php',
            'filament-tasks-api'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/filament-tasks-api.php' => config_path('filament-tasks-api.php'),
        ], 'filament-tasks-api-config');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'filament-tasks-api');
    }
}
