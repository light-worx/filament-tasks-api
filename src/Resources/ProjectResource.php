<?php

namespace Lightworx\FilamentTasks\Resources;

use BackedEnum;
use Closure;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Lightworx\FilamentTasks\FilamentTasksPlugin;
use Lightworx\FilamentTasks\Models\Project;
use Lightworx\FilamentTasks\Resources\ProjectResource\Pages;
use Lightworx\FilamentTasks\Resources\ProjectResource\Schemas\ProjectForm;
use Lightworx\FilamentTasks\Resources\ProjectResource\Tables\ProjectsTable;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    public static function getNavigationGroup(): ?string
    {
        return FilamentTasksPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return FilamentTasksPlugin::get()->getNavigationSort() + 1;
    }

    public static function getNavigationLabel(): string { return 'Projects'; }
    public static function getModelLabel(): string { return 'Project'; }
    public static function getPluralModelLabel(): string { return 'Projects'; }

    public static function resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null): ?Model
    {
        $project = new Project();
        $project->forceFill(['id' => (string) $key]);
        $project->exists = true;
        return $project;
    }

    public static function form(Schema $schema): Schema
    {
        return ProjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectsTable::configure($table);
    }

    public static function getRecordRouteKeyName(): ?string { return 'id'; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit'   => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}