<?php

namespace Lightworx\FilamentTasks\Resources;

use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Lightworx\FilamentTasks\FilamentTasksPlugin;
use Lightworx\FilamentTasks\Models\Project;
use Lightworx\FilamentTasks\Resources\ProjectResource\Pages;
use Lightworx\TasksApiClient\Facades\TasksApi;

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
        return $schema->schema([
            TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Textarea::make('description')
                ->label('Description')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
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