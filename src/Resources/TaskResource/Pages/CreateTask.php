<?php

namespace Lightworx\FilamentTasks\Resources\TaskResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Lightworx\FilamentTasks\Models\Task;
use Lightworx\FilamentTasks\Resources\TaskResource;
use Lightworx\TasksApiClient\TasksApiClient;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    protected function handleRecordCreation(array $data): Task
    {
        /** @var TasksApiClient $client */
        $client = app(TasksApiClient::class);

        try {
            $dto = $client->tasks()->create($data);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Failed to create task: ' . $e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }

        return Task::fromDto($dto);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
