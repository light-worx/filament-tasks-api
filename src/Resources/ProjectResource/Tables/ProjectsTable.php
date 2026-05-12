<?php

namespace Lightworx\FilamentTasks\Resources\ProjectResource\Tables;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Lightworx\FilamentTasks\Models\Project;
use Lightworx\TasksApiClient\Facades\TasksApi;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->width('80px')
                    ->copyable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(60)
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y'),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Project $record) {
                        try {
                            TasksApi::projects()->delete($record->id);
                            Notification::make()->title('Project deleted.')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Failed: ' . $e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }
}