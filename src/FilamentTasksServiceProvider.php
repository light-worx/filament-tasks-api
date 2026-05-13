<?php

namespace Lightworx\FilamentTasks;

use Illuminate\Support\ServiceProvider;
use Lightworx\TasksApiClient\DTO\TaskData;

class FilamentTasksServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/filament-tasks.php',
            'filament-tasks'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/filament-tasks.php' => config_path('filament-tasks.php'),
        ], 'filament-tasks-config');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'filament-tasks');
        class_exists(TaskData::class);
    }
}
