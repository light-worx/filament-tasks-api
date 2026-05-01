<?php

namespace Lightworx\FilamentTasksApi;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Lightworx\FilamentTasksApi\Resources\TaskResource;
use Lightworx\FilamentTasksApi\Widgets\TaskStatsWidget;

class FilamentTasksApiPlugin implements Plugin
{
    protected string $navigationGroup = '';
    protected ?int $navigationSort = null;
    protected bool $withStatsWidget = false;

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-tasks-api';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            TaskResource::class,
        ]);

        if ($this->withStatsWidget) {
            $panel->widgets([
                TaskStatsWidget::class,
            ]);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public function withStatsWidget(bool $value = true): static
    {
        $this->withStatsWidget = $value;

        return $this;
    }

    // ──────────────────────────────────────────────
    // Fluent configuration helpers
    // ──────────────────────────────────────────────

    public function navigationGroup(string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): string
    {
        return $this->navigationGroup ?: config('filament-tasks-api.navigation_group', 'Task Management');
    }

    public function navigationSort(int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function getNavigationSort(): int
    {
        return $this->navigationSort ?? (int) config('filament-tasks-api.navigation_sort', 10);
    }

    // ──────────────────────────────────────────────
    // Global accessor (used by resources/pages)
    // ──────────────────────────────────────────────

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament('filament-tasks-api');

        return $plugin;
    }
}
