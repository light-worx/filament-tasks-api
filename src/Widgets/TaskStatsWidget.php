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
            $stats  = $client->stats();
        } catch (\Throwable) {
            return [
                Stat::make('Tasks', 'Unavailable')
                    ->description('Could not reach the Tasks API')
                    ->color('danger'),
            ];
        }

        return [
            Stat::make('Pending', $stats['pending'] ?? 0)
                ->description('Tasks awaiting action')
                ->color('warning')
                ->icon('heroicon-o-clock'),

            Stat::make('In Progress', $stats['in_progress'] ?? 0)
                ->description('Currently being worked on')
                ->color('primary')
                ->icon('heroicon-o-arrow-path'),

            Stat::make('Completed', $stats['completed'] ?? 0)
                ->description('Done!')
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Cancelled', $stats['cancelled'] ?? 0)
                ->description('No longer needed')
                ->color('danger')
                ->icon('heroicon-o-x-circle'),
        ];
    }
}
