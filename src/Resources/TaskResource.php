<?php

namespace Lightworx\FilamentTasks\Resources;

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
use Lightworx\FilamentTasks\FilamentTasksPlugin;
use Lightworx\FilamentTasks\Models\Task;
use Lightworx\FilamentTasks\Resources\TaskResource\Pages;
use Lightworx\FilamentTasks\Support\StatusHelper;
use Lightworx\TasksApiClient\Facades\TasksApi;
use Lightworx\TasksApiClient\TasksApiClient;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function getNavigationGroup(): ?string
    {
        return FilamentTasksPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return FilamentTasksPlugin::get()->getNavigationSort();
    }

    public static function getNavigationLabel(): string { return 'Tasks'; }
    public static function getModelLabel(): string { return 'Task'; }
    public static function getPluralModelLabel(): string { return 'Tasks'; }

    // ──────────────────────────────────────────────────────────────────────
    // Route binding — return a shell Task; no DB lookup
    // ──────────────────────────────────────────────────────────────────────

    public static function resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null): ?Model
    {
        $task = new Task();
        $task->forceFill(['id' => (string) $key]);
        $task->exists = true;
        return $task;
    }

    // ──────────────────────────────────────────────
    // Form
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

            // Status: options AND the label for the current value both come
            // from the API. getOptionLabelUsing() ensures the selected label
            // is resolved even when options are loaded lazily.
            Select::make('status')
                ->label('Status')
                ->options(fn () => StatusHelper::options())
                ->getOptionLabelUsing(fn (?string $value) => StatusHelper::label($value))
                ->required(),

            // Project: searchable Select with explicit label resolver so the
            // current project name is shown rather than the raw id.
            Select::make('project_id')
                ->label('Project')
                ->options(fn () => StatusHelper::projectOptions())
                ->getOptionLabelUsing(fn (?string $value) => StatusHelper::projectLabel($value))
                ->searchable()
                ->nullable(),

            TextInput::make('assigned_email')
                ->label('Assigned To (email)')
                ->email()
                ->maxLength(255),

            DateTimePicker::make('due_at')
                ->label('Due At'),
        ]);
    }

    // ──────────────────────────────────────────────
    // Table
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
                    ->visible(function (Task $record) {
                        $completeId = static::completeStatusId();
                        return $completeId && $record->status !== $completeId;
                    })
                    ->requiresConfirmation()
                    ->action(function (Task $record) {
                        $completeId = static::completeStatusId();
                        if (! $completeId) {
                            Notification::make()->title('No "completed" status found in API.')->warning()->send();
                            return;
                        }
                        try {
                            app(TasksApiClient::class)
                                ->tasks()
                                ->update($record->id, ['status' => $completeId]);
                            Notification::make()->title('Task marked as completed.')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Failed: ' . $e->getMessage())->danger()->send();
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
                            app(TasksApiClient::class)->tasks()->delete($record->id);
                            Notification::make()->title('Task deleted.')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Failed: ' . $e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $tasks = app(TasksApiClient::class)->tasks();
                            $records->each(fn (Task $r) => $tasks->delete($r->id));
                            Notification::make()->title('Selected tasks deleted.')->success()->send();
                        }),
                ]),
            ])
            ->poll('30s');
    }

    protected static function completeStatusId(): ?string
    {
        return collect(StatusHelper::all())
            ->first(fn ($s) => str_contains(strtolower($s['label'] ?? ''), 'complet'))['id'] ?? null;
    }

    public static function getRecordRouteKeyName(): ?string { return 'id'; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit'   => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}