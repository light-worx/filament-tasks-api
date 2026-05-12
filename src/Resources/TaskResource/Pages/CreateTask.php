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
        $dto = TasksApi::tasks()->create($data);
        TaskCache::flush();
        return Task::fromDto($dto);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}