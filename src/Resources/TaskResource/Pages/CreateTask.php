<?php

namespace Lightworx\FilamentTasksApi\Resources\TaskResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Lightworx\FilamentTasksApi\Models\Task;
use Lightworx\FilamentTasksApi\Resources\TaskResource;
use Lightworx\TasksApiClient\TasksApiClient;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    /**
     * Instead of persisting via Eloquent, we POST to the remote API.
     */
    protected function handleRecordCreation(array $data): Task
    {
        /** @var TasksApiClient $client */
        $client = app(TasksApiClient::class);

        try {
            $response = $client->create($data);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Failed to create task: ' . $e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }

        return (new Task())->forceFill($response['data'] ?? $response);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
