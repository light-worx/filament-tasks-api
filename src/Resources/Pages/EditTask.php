<?php

namespace Lightworx\FilamentTasksApi\Resources\Pages;

use Filament\Resources\Pages\EditRecord;
use Lightworx\FilamentTasksApi\Resources\TaskResource;
use Lightworx\FilamentTasksApi\Services\TaskService;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected function handleRecordUpdate($record, array $data): array
    {
        return app(TaskService::class)->update($record['id'], $data);
    }

    protected function handleRecordDeletion($record): void
    {
        app(TaskService::class)->delete($record['id']);
    }
}