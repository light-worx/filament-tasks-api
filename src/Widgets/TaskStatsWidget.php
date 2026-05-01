<?php

namespace Lightworx\FilamentTasksApi\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Lightworx\TasksApiClient\TasksApiClient;

class TaskStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        try {
            /** @var TasksApiClient $client */
            $client = app(TasksApiClient::class);

            $all = $client->tasks()->get(); // returns TaskData[]

            $counts = collect($all)->countBy(fn ($dto) => $dto->status ?? 'unknown');

        } catch (\Throwable) {
            return [
                Stat::make('Tasks', 'Unavailable')
                    ->description('Could not reach the Tasks API')
                    ->color('danger'),
            ];
        }

        return [
            Stat::make('Pending', $counts->get('pending', 0))
                ->color('warning')
                ->icon('heroicon-o-clock'),

            Stat::make('In Progress', $counts->get('in_progress', 0))
                ->color('primary')
                ->icon('heroicon-o-arrow-path'),

            Stat::make('Completed', $counts->get('completed', 0))
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Cancelled', $counts->get('cancelled', 0))
                ->color('danger')
                ->icon('heroicon-o-x-circle'),
        ];
    }
}
