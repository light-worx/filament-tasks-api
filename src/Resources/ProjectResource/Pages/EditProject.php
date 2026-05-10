<?php

namespace Lightworx\FilamentTasks\Resources\ProjectResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Lightworx\FilamentTasks\Models\Project;
use Lightworx\FilamentTasks\Resources\ProjectResource;
use Lightworx\TasksApiClient\Facades\TasksApi;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function resolveRecord(int|string $key): Project
    {
        try {
            $dto = TasksApi::projects()->find((string) $key);
            if ($dto) {
                return Project::fromDto($dto);
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Could not load project: ' . $e->getMessage())
                ->danger()
                ->send();
        }

        $project = new Project();
        $project->forceFill(['id' => (string) $key]);
        $project->exists = true;
        return $project;
    }

    protected function handleRecordUpdate(Model $record, array $data): Project
    {
        try {
            return Project::fromDto(
                TasksApi::projects()->update((string) $record->id, $data)
            );
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Failed to update project: ' . $e->getMessage())
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