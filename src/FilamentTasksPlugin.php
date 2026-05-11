<?php

namespace Lightworx\FilamentTasks;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Lightworx\FilamentTasks\Resources\ProjectResource;
use Lightworx\FilamentTasks\Resources\TaskResource;
use Lightworx\FilamentTasks\Widgets\TaskStatsWidget;

class FilamentTasksPlugin implements Plugin
{
    protected string $navigationGroup = '';
    protected ?int $navigationSort = null;
    protected bool $withStatsWidget = false;
    protected bool $withProjects = true;

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
        $resources = [TaskResource::class];

        if ($this->withProjects) {
            $resources[] = ProjectResource::class;
        }

        $panel->resources($resources);

        if ($this->withStatsWidget) {
            $panel->widgets([TaskStatsWidget::class]);
        }
    }

    public function boot(Panel $panel): void {}

    public function withStatsWidget(bool $value = true): static
    {
        $this->withStatsWidget = $value;
        return $this;
    }

    public function withProjects(bool $value = true): static
    {
        $this->withProjects = $value;
        return $this;
    }

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

    /**
     * The email address to scope all task queries to.
     * Required if the API client does not have can_view_all_tasks.
     */
    public function getAssignedEmail(): ?string
    {
        $email = config('filament-tasks.assigned_email', '');
        return filled($email) ? $email : null;
    }

    /**
     * Owner email for unlocking private project visibility.
     */
    public function getOwnerEmail(): ?string
    {
        $email = config('filament-tasks.owner_email', '');
        return filled($email) ? $email : null;
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament('filament-tasks');
        return $plugin;
    }
}