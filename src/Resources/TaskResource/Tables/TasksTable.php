<?php

namespace Lightworx\FilamentTasks\Resources\TaskResource\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Lightworx\FilamentTasks\Support\StatusHelper;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->width('80px')
                    ->copyable(),

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('status')
                    ->badge()
                    ->label('Status')
                    ->color(fn (?string $state) => StatusHelper::badgeColour($state))
                    ->formatStateUsing(fn (?string $state) => StatusHelper::label($state)),

                TextColumn::make('project_id')
                    ->label('Project')
                    ->formatStateUsing(fn (?string $state) => StatusHelper::projectLabel($state)),

                TextColumn::make('assigned_email')
                    ->label('Assigned To'),

                TextColumn::make('due_at')
                    ->label('Due At')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(fn () => StatusHelper::options()),
            ])
            ->recordActions([
                Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== StatusHelper::doneStatusLabel())
                    ->requiresConfirmation()
                    ->action(fn ($record, $livewire) => $livewire->completeTask($record->id)),

                EditAction::make(),

                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($record, $livewire) => $livewire->deleteTask($record->id)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_delete')
                        ->label('Delete selected')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records, $livewire) => $livewire->deleteBulkTasks($records))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->poll('30s');
    }
}