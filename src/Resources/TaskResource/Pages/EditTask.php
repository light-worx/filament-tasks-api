<?php

namespace Lightworx\FilamentTasksApi\Resources\TaskResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Lightworx\FilamentTasksApi\Models\Task;
use Lightworx\FilamentTasksApi\Resources\TaskResource;
use Lightworx\TasksApiClient\TasksApiClient;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    /**
     * Called once on the initial GET. At this point $this->record is already
     * a shell Task (set by resolveRecordRouteBinding in TaskResource).
     * We fetch the full data from the API so the form is pre-populated.
     */
    protected function resolveRecord(int|string $key): Task
    {
        /** @var TasksApiClient $client */
        $client = app(TasksApiClient::class);

        try {
            $dtos  = $client->tasks()->get();
            $dto   = collect($dtos)->first(fn ($d) => $d->id === (string) $key);

            if ($dto) {
                return Task::fromDto($dto);
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Could not load task: ' . $e->getMessage())
                ->danger()
                ->send();
        }

        // Fallback shell — form will be empty but save still works
        $task = new Task();
        $task->forceFill(['id' => (string) $key]);
        $task->exists = true;

        return $task;
    }

    protected function handleRecordUpdate(Model $record, array $data): Task
    {
        /** @var TasksApiClient $client */
        $client = app(TasksApiClient::class);

        try {
            $dto = $client->tasks()->update($record->id, $data);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Failed to update task: ' . $e->getMessage())
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