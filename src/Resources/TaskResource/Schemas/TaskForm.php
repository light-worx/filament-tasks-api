<?php

namespace Lightworx\FilamentTasks\Resources\TaskResource\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Lightworx\FilamentTasks\Support\StatusHelper;

class TaskForm
{
    public static function configure(Schema $schema): Schema
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
}