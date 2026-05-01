<?php

namespace Lightworx\FilamentTasksApi\Resources;

use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Lightworx\FilamentTasksApi\FilamentTasksApiPlugin;
use Lightworx\FilamentTasksApi\Models\Task;
use Lightworx\FilamentTasksApi\Resources\TaskResource\Pages;
use Lightworx\TasksApiClient\Facades\TasksApi;
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

    // ──────────────────────────────────────────────────────────────────────
    // Route binding — bypass DB entirely
    // ──────────────────────────────────────────────────────────────────────

    public static function resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null): ?Model
    {
        $task = new Task();
        $task->forceFill(['id' => (string) $key]);
        $task->exists = true;

        return $task;
    }

    // ──────────────────────────────────────────────
    // Form schema
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
                ->options(fn () => TasksApi::meta()->statusOptions())
                ->default('pending')
                ->required(),

            TextInput::make('assigned_email')
                ->label('Assigned To (email)')
                ->email()
                ->maxLength(255),

            TextInput::make('project_id')
                ->label('Project ID')
                ->maxLength(255),

            DateTimePicker::make('due_at')
                ->label('Due At'),
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
                    ->width('80px')
                    ->copyable(),

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('status')
                    ->badge()
                    ->label('Status')
                    ->color(fn (?string $state) => match ($state) {
                        'pending'     => 'warning',
                        'in_progress' => 'primary',
                        'completed'   => 'success',
                        'cancelled'   => 'danger',
                        default       => 'gray',
                    })
                    ->formatStateUsing(function (?string $state): string {
                        try {
                            $options = TasksApi::meta()->statusOptions();
                            return $options[$state] ?? $state ?? '—';
                        } catch (\Throwable) {
                            return $state ?? '—';
                        }
                    }),

                TextColumn::make('assigned_email')
                    ->label('Assigned To'),

                TextColumn::make('project_id')
                    ->label('Project'),

                TextColumn::make('due_at')
                    ->label('Due At')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(fn () => TasksApi::meta()->statusOptions()),
            ])
            ->recordActions([
                Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Task $record) => $record->status !== 'completed')
                    ->requiresConfirmation()
                    ->action(function (Task $record) {
                        try {
                            app(TasksApiClient::class)
                                ->tasks()
                                ->update($record->id, ['status' => 'completed']);

                            Notification::make()
                                ->title('Task marked as completed.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Failed: ' . $e->getMessage())
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
                            app(TasksApiClient::class)
                                ->tasks()
                                ->delete($record->id);

                            Notification::make()
                                ->title('Task deleted.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Failed: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $query = app(TasksApiClient::class)->tasks();
                            $records->each(fn (Task $record) => $query->delete($record->id));

                            Notification::make()
                                ->title('Selected tasks deleted.')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->poll('30s');
    }

    // ──────────────────────────────────────────────
    // Pages
    // ──────────────────────────────────────────────

    public static function getRecordRouteKeyName(): ?string
    {
        return 'id';
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit'   => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}