<?php

namespace Lightworx\FilamentTasksApi\Resources\TaskResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Lightworx\FilamentTasksApi\Models\Task;
use Lightworx\FilamentTasksApi\Resources\TaskResource;
use Lightworx\TasksApiClient\TasksApiClient;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    /**
     * Load the task from the remote API.
     */
    protected function resolveRecord(int|string $key): Task
    {
        /** @var TasksApiClient $client */
        $client = app(TasksApiClient::class);

        try {
            $response = $client->show($key);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Task not found: ' . $e->getMessage())
                ->danger()
                ->send();

            $this->redirect($this->getResource()::getUrl('index'));
        }

        $data = $response['data'] ?? $response;

        return (new Task())->forceFill($data);
    }

    /**
     * Persist changes back to the remote API.
     */
    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): Task
    {
        /** @var TasksApiClient $client */
        $client = app(TasksApiClient::class);

        try {
            $response = $client->update($record->id, $data);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Failed to update task: ' . $e->getMessage())
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
