<?php

namespace Lightworx\FilamentTasks\Resources\TaskResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Lightworx\FilamentTasks\Models\Task;
use Lightworx\FilamentTasks\Resources\TaskResource;
use Lightworx\TasksApiClient\Facades\TasksApi;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected function resolveRecord(int|string $key): Task
    {
        try {
            // SDK now has find() — no need to fetch all tasks
            $dto = TasksApi::tasks()->find((string) $key);
            if ($dto) {
                return Task::fromDto($dto);
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Could not load task: ' . $e->getMessage())
                ->danger()
                ->send();
        }

        $task = new Task();
        $task->forceFill(['id' => (string) $key]);
        $task->exists = true;
        return $task;
    }

    protected function handleRecordUpdate(Model $record, array $data): Task
    {
        try {
            $dto = TasksApi::tasks()->update($record->id, $data);
            return Task::fromDto($dto);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Failed to update task: ' . $e->getMessage())
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