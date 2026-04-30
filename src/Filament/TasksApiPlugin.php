<?php

namespace Lightworx\FilamentTasksApi\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Lightworx\FilamentTasksApi\Resources\TaskResource;

class TasksApiPlugin implements Plugin
{
    public function getId(): string
    {
        return 'tasks-api';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            TaskResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        // Optional: register hooks, navigation, etc
    }
}