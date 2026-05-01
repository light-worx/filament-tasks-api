<?php

namespace Lightworx\FilamentTasksApi\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Lightworx\FilamentTasksApi\FilamentTasksApiPlugin;
use Lightworx\FilamentTasksApi\Models\Task;
use Lightworx\FilamentTasksApi\Resources\TaskResource\Pages;
use Lightworx\TasksApiClient\TasksApiClient;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function getNavigationGroup(): ?string
    {
        return FilamentTasksApiPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return FilamentTasksApiPlugin::get()->getNavigationSort();
    }

    public static function getNavigationLabel(): string
    {
        return 'Tasks';
    }

    public static function getModelLabel(): string
    {
        return 'Task';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Tasks';
    }

    // ──────────────────────────────────────────────
    // Form schema (Create & Edit)
    // ──────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('title')
                ->label('Title')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Textarea::make('description')
                ->label('Description')
                ->rows(3)
                ->columnSpanFull(),

            Select::make('status')
                ->label('Status')
                ->options([
                    'pending'     => 'Pending',
                    'in_progress' => 'In Progress',
                    'completed'   => 'Completed',
                    'cancelled'   => 'Cancelled',
                ])
                ->default('pending')
                ->required(),

            Select::make('priority')
                ->label('Priority')
                ->options([
                    'low'    => 'Low',
                    'medium' => 'Medium',
                    'high'   => 'High',
                ])
                ->default('medium')
                ->required(),

            DatePicker::make('due_date')
                ->label('Due Date'),

            TextInput::make('assigned_to')
                ->label('Assigned To')
                ->maxLength(255),
        ]);
    }

    // ──────────────────────────────────────────────
    // Table schema
    // ──────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->width('60px'),

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('status')
                    ->badge()
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'in_progress',
                        'success' => 'completed',
                        'danger'  => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending'     => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed'   => 'Completed',
                        'cancelled'   => 'Cancelled',
                        default       => $state,
                    }),

                TextColumn::make('priority')
                    ->badge()
                    ->label('Priority')
                    ->colors([
                        'success' => 'low',
                        'warning' => 'medium',
                        'danger'  => 'high',
                    ])
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('assigned_to')
                    ->label('Assigned To'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending'     => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed'   => 'Completed',
                        'cancelled'   => 'Cancelled',
                    ]),

                SelectFilter::make('priority')
                    ->options([
                        'low'    => 'Low',
                        'medium' => 'Medium',
                        'high'   => 'High',
                    ]),
            ])
            ->recordActions([
                Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Task $record) => $record->status !== 'completed')
                    ->action(function (Task $record) {
                        try {
                            app(TasksApiClient::class)->update($record->id, ['status' => 'completed']);
                            Notification::make()
                                ->title('Task marked as completed.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Failed to update task: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                EditAction::make(),

                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Task $record) {
                        try {
                            app(TasksApiClient::class)->delete($record->id);
                            Notification::make()
                                ->title('Task deleted.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Failed to delete task: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $client = app(TasksApiClient::class);
                            $records->each(fn (Task $record) => $client->delete($record->id));
                            Notification::make()->title('Selected tasks deleted.')->success()->send();
                        }),
                ]),
            ])
            ->poll('30s');
    }

    // ──────────────────────────────────────────────
    // Pages
    // ──────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit'   => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
