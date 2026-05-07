<?php

namespace Lightworx\FilamentTasks;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Lightworx\FilamentTasks\Resources\TaskResource;
use Lightworx\FilamentTasks\Widgets\TaskStatsWidget;

class FilamentTasksPlugin implements Plugin
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
        return 'filament-tasks';
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
        return $this->navigationGroup ?: config('filament-tasks.navigation_group', 'Task Management');
    }

    public function navigationSort(int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function getNavigationSort(): int
    {
        return $this->navigationSort ?? (int) config('filament-tasks.navigation_sort', 10);
    }

    // ──────────────────────────────────────────────
    // Global accessor (used by resources/pages)
    // ──────────────────────────────────────────────

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament('filament-tasks');

        return $plugin;
    }
}
