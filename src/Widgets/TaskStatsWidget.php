<?php

namespace Lightworx\FilamentTasks\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Lightworx\TasksApiClient\Facades\TasksApi;

class TaskStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        try {
            $all      = TasksApi::tasks()->get();         // TaskData[]
            $counts   = collect($all)->countBy(fn ($dto) => $dto->status ?? 'unknown');
            $statuses = TasksApi::meta()->statusOptions(); // ['pending' => 'Pending', ...]
        } catch (\Throwable) {
            return [
                Stat::make('Tasks', 'Unavailable')
                    ->description('Could not reach the Tasks API')
                    ->color('danger'),
            ];
        }

        $colorMap = [
            'pending'     => 'warning',
            'in_progress' => 'primary',
            'completed'   => 'success',
            'cancelled'   => 'danger',
        ];

        return collect($statuses)
            ->map(fn (string $label, string $id) => Stat::make($label, $counts->get($id, 0))
                ->color($colorMap[$id] ?? 'gray')
            )
            ->values()
            ->all();
    }
}