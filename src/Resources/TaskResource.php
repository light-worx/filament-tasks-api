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
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Lightworx\FilamentTasks\FilamentTasksPlugin;
use Lightworx\FilamentTasks\Models\Task;
use Lightworx\FilamentTasks\Resources\TaskResource\Pages;
use Lightworx\FilamentTasks\Support\StatusHelper;
use Lightworx\FilamentTasks\Support\TaskCache;
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

    public static function resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null): ?Model
    {
        $task = new Task();
        $task->forceFill(['id' => (string) $key]);
        $task->exists = true;
        return $task;
    }

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
                ->options(fn () => StatusHelper::options())
                ->required(),
            Select::make('project_id')
                ->label('Project')
                ->options(fn () => StatusHelper::projectOptions())
                ->getOptionLabelUsing(fn (?string $value) => StatusHelper::projectLabel($value))
                ->searchable()
                ->required(),
            TextInput::make('assigned_email')
                ->label('Assigned To (email)')
                ->email()
                ->required()
                ->maxLength(255),
            DateTimePicker::make('due_at')
                ->label('Due At'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->wrap(),
               TextColumn::make('status')
                    ->badge()
                    ->label('Status')
                    ->color(function ($state) { return Color::hex(StatusHelper::badgeColour($state)); })
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
                TextColumn::make('created_at')
                    ->label('Created At')
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
                    ->visible(function ($record) {
                        $doneLabel = static::doneStatusLabel();
                        return $doneLabel && $record->status !== $doneLabel;
                    })
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $doneLabel = static::doneStatusLabel();
                        if (! $doneLabel) {
                            Notification::make()->title('No done status found in API.')->warning()->send();
                            return;
                        }
                        try {
                            app(TasksApiClient::class)
                                ->tasks()
                                ->update($record->id, ['status' => $doneLabel]);
                            TaskCache::flush();
                            Notification::make()->title('Task marked as done.')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Failed: ' . $e->getMessage())->danger()->send();
                        }
                    }),

                EditAction::make(),
                Action::make('delete')
                    ->requiresConfirmation()
                    ->action(fn (Task $record) => $record->delete())
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $tasks = app(TasksApiClient::class)->tasks();
                            $records->each(fn (Task $r) => $tasks->delete($r->id));
                            TaskCache::flush();
                            Notification::make()->title('Selected tasks deleted.')->success()->send();
                        }),
                ]),
            ])
            ->poll('30s');
    }

    /**
     * Find the status label that represents "done" — the last active status
     * by sort_order, which is the natural end state in most workflows.
     * Falls back to searching for labels containing "done" or "complet".
     */
    protected static function doneStatusLabel(): ?string
    {
        $statuses = collect(StatusHelper::all())
            ->filter(fn ($s) => ($s['is_active'] ?? true))
            ->sortBy('sort_order');

        // Try label match first
        $match = $statuses->first(
            fn ($s) => str_contains(strtolower($s['label']), 'done')
                || str_contains(strtolower($s['label']), 'complet')
        );

        if ($match) {
            return $match['label'];
        }

        // Fall back to the last status by sort_order
        return $statuses->last()['label'] ?? null;
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