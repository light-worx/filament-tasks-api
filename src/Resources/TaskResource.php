<?php

namespace Lightworx\FilamentTasksApi\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;
use Lightworx\FilamentTasksApi\Services\TaskService;
use Lightworx\FilamentTasksApi\Support\Paginators\ApiPaginator;

class TaskResource extends Resource
{
    protected static ?string $navigationIcon = null;

    public static function getNavigationIcon(): string
    {
        return config('filament-tasks-api.navigation_icon');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('filament-tasks-api.navigation_group');
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->poll(config('filament-tasks-api.polling_interval'))
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('assigned_email'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                    ]),
                Tables\Columns\TextColumn::make('project_id'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                    ]),
            ])
            ->records(function ($livewire) {

                $filters = [
                    'status' => $livewire->tableFilters['status']['value'] ?? null,
                ];

                $response = app(TaskService::class)->paginate($filters);

                return ApiPaginator::fromApi($response);
            });
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required(),
            Forms\Components\TextInput::make('assigned_email')->email(),
            Forms\Components\TextInput::make('project_id'),
            Forms\Components\Textarea::make('description'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}