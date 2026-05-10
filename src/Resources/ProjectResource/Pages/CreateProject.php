<?php

namespace Lightworx\FilamentTasks\Resources\ProjectResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Lightworx\FilamentTasks\Models\Project;
use Lightworx\FilamentTasks\Resources\ProjectResource;
use Lightworx\TasksApiClient\Facades\TasksApi;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected function handleRecordCreation(array $data): Project
    {
        try {
            return Project::fromDto(TasksApi::projects()->create($data));
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Failed to create project: ' . $e->getMessage())
                ->danger()
                ->send();
            $this->halt();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}