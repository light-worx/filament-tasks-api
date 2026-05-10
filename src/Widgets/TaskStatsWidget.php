<?php

namespace Lightworx\FilamentTasks\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Lightworx\FilamentTasks\Support\StatusHelper;
use Lightworx\TasksApiClient\Facades\TasksApi;

class TaskStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        try {
            $counts   = collect(TasksApi::tasks()->get())
                ->countBy(fn ($dto) => $dto->status ?? 'unknown');
            $statuses = StatusHelper::all(); // indexed by id, includes colour
        } catch (\Throwable) {
            return [
                Stat::make('Tasks', 'Unavailable')
                    ->description('Could not reach the Tasks API')
                    ->color('danger'),
            ];
        }

        return collect($statuses)
            ->filter(fn ($s) => ($s['is_active'] ?? true))
            ->sortBy('sort_order')
            ->map(fn ($s) => Stat::make(
                $s['label'],
                $counts->get($s['id'], 0)
            )->color($s['colour'] ?? 'gray'))
            ->values()
            ->all();
    }
}