<?php

namespace Lightworx\FilamentTasksApi\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Lightworx\FilamentTasksApi\Resources\TaskResource;
use Lightworx\FilamentTasksApi\Services\TaskService;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    protected function handleRecordCreation(array $data): array
    {
        return app(TaskService::class)->create($data);
    }
}