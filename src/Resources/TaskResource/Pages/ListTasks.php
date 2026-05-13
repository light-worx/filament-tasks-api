<?php

namespace Lightworx\FilamentTasks\Resources\TaskResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\LazyCollection;
use Lightworx\FilamentTasks\Models\Task;
use Lightworx\FilamentTasks\Resources\TaskResource;
use Lightworx\FilamentTasks\Support\StatusHelper;
use Lightworx\FilamentTasks\Support\TaskCache;
use Lightworx\TasksApiClient\DTO\TaskData;
use Lightworx\TasksApiClient\Facades\TasksApi;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected ?LengthAwarePaginator $apiPaginator = null;

    public int $seenCacheVersion = 0;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->apiPaginator     = null;
                    $this->seenCacheVersion = 0;
                    Notification::make()->title('Tasks refreshed.')->success()->send();
                }),

            CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return Task::query()->whereRaw('1 = 0');
    }

    public function getTableRecords(): \Illuminate\Contracts\Pagination\LengthAwarePaginator|Collection
    {
        $currentVersion = (int) Cache::get('filament_tasks:version', 0);
        if ($currentVersion !== $this->seenCacheVersion) {
            $this->apiPaginator     = null;
            $this->seenCacheVersion = $currentVersion;
        }

        if ($this->apiPaginator !== null) {
            return $this->apiPaginator;
        }

        $perPage      = $this->getTableRecordsPerPage();
        $page         = max(1, $this->getPage());
        $tableFilters = $this->getTableFilters();
        $status       = $tableFilters['status']['value'] ?? null;

        try {
            $result = TaskCache::tasks($page, $perPage, $status ?: null);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Could not load tasks: ' . $e->getMessage())
                ->danger()
                ->send();
            $this->apiPaginator = new LengthAwarePaginator([], 0, $perPage, $page);
            return $this->apiPaginator;
        }

        $items = collect($result['data'])->map(fn (TaskData $dto) => Task::fromDto($dto));
        $meta  = $result['meta'] ?? [];

        $this->apiPaginator = new LengthAwarePaginator(
            $items,
            $meta['total']        ?? count($items),
            $meta['per_page']     ?? $perPage,
            $meta['current_page'] ?? $page,
            ['path' => request()->url()]
        );

        return $this->apiPaginator;
    }

    /**
     * Resolve a single record for row actions by ID rather than array index.
     */
    protected function resolveTableRecord(?string $key): Model|array|null
    {
        if ($key === null) return null;

        $records = $this->getTableRecords();
        $items   = $records instanceof LengthAwarePaginator
            ? $records->getCollection()
            : collect($records);

        return $items->first(fn (Task $task) => (string) $task->id === (string) $key);
    }

    /**
     * Resolve selected records for bulk actions.
     * Filament falls back to an Eloquent query when this isn't overridden,
     * which returns nothing since Task has no real DB table.
     * We look up each selected key from the current page's records instead.
     */
    public function getSelectedTableRecords(bool $shouldFetchSelectedRecords = true, ?int $chunkSize = null): EloquentCollection|Collection|LazyCollection
    {
        $records = $this->getTableRecords();
        $items   = $records instanceof LengthAwarePaginator
            ? $records->getCollection()
            : collect($records);

        // $this->selectedTableRecords holds the selected keys
        $selectedKeys = collect($this->selectedTableRecords)->map(fn ($k) => (string) $k);

        return $items->filter(
            fn (Task $task) => $selectedKeys->contains((string) $task->id)
        )->values();
    }

    // ──────────────────────────────────────────────
    // Mutation methods called from TasksTable via $livewire
    // ──────────────────────────────────────────────

    public function completeTask(string $id): void
    {
        $doneLabel = StatusHelper::doneStatusLabel();

        if (! $doneLabel) {
            Notification::make()->title('No done status found.')->warning()->send();
            return;
        }

        try {
            TasksApi::tasks()->update($id, ['status' => $doneLabel]);
            $this->afterMutation('Task marked as done.');
        } catch (\Throwable $e) {
            Notification::make()->title('Failed: ' . $e->getMessage())->danger()->send();
        }
    }

    public function deleteTask(string $id): void
    {
        try {
            TasksApi::tasks()->delete($id);
            $this->afterMutation('Task deleted.');
        } catch (\Throwable $e) {
            Notification::make()->title('Failed: ' . $e->getMessage())->danger()->send();
        }
    }

    public function deleteBulkTasks(mixed $records): void
    {
        try {
            collect($records)->each(fn (Task $record) => TasksApi::tasks()->delete($record->id));
            $this->afterMutation(collect($records)->count() . ' tasks deleted.');
        } catch (\Throwable $e) {
            Notification::make()->title('Failed: ' . $e->getMessage())->danger()->send();
        }
    }

    protected function afterMutation(string $message): void
    {
        TaskCache::flush();
        $this->apiPaginator     = null;
        $this->seenCacheVersion = (int) Cache::get('filament_tasks:version', 0);
        Notification::make()->title($message)->success()->send();
    }
}