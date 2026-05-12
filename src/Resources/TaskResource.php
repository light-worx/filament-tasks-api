<?php

namespace Lightworx\FilamentTasks\Resources;

use BackedEnum;
use Closure;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Lightworx\FilamentTasks\FilamentTasksPlugin;
use Lightworx\FilamentTasks\Models\Task;
use Lightworx\FilamentTasks\Resources\TaskResource\Pages;
use Lightworx\FilamentTasks\Resources\TaskResource\Schemas\TaskForm;
use Lightworx\FilamentTasks\Resources\TaskResource\Tables\TasksTable;

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
        return TaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TasksTable::configure($table);
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