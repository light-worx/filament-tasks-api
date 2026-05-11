<?php

namespace Lightworx\FilamentTasks\Resources\TaskResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Lightworx\FilamentTasks\Models\Task;
use Lightworx\FilamentTasks\Resources\TaskResource;
use Lightworx\FilamentTasks\Support\TaskCache;
use Lightworx\TasksApiClient\Facades\TasksApi;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    protected function handleRecordCreation(array $data): Task
    {
        try {
            $task = Task::fromDto(TasksApi::tasks()->create($data));
            TaskCache::flush();
            return $task;
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Failed to create task: ' . $e->getMessage())
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