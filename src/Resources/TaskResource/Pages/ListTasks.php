<?php

namespace Lightworx\FilamentTasksApi\Resources\TaskResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Lightworx\FilamentTasksApi\Models\Task;
use Lightworx\FilamentTasksApi\Resources\TaskResource;
use Lightworx\TasksApiClient\TasksApiClient;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    /** Cached API paginator for this request cycle. */
    protected ?LengthAwarePaginator $apiPaginator = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // Filament calls getTableQuery() during page setup. We return a valid
    // Builder (Task model points to the `migrations` table so no missing-
    // table error) locked to always return zero rows.
    // The actual API data is supplied entirely via getTableRecords() below.
    // ──────────────────────────────────────────────────────────────────────

    protected function getTableQuery(): Builder
    {
        return Task::query()->whereRaw('1 = 0');
    }

    // ──────────────────────────────────────────────────────────────────────
    // Filament 5 calls getTableRecords() to populate the table rows.
    // We override it to pull from the remote API instead.
    // ──────────────────────────────────────────────────────────────────────

    public function getTableRecords(): \Illuminate\Contracts\Pagination\LengthAwarePaginator|Collection
    {
        if ($this->apiPaginator !== null) {
            return $this->apiPaginator;
        }

        /** @var TasksApiClient $client */
        $client = app(TasksApiClient::class);

        $perPage = $this->getTableRecordsPerPage();
        $page    = max(1, $this->getPage());
        $search  = $this->getTableSearch();

        // Collect active filter values
        $tableFilters   = $this->getTableFilters();
        $statusFilter   = $tableFilters['status']['value']   ?? null;
        $priorityFilter = $tableFilters['priority']['value'] ?? null;

        $params = array_filter([
            'per_page' => $perPage,
            'page'     => $page,
            'search'   => $search ?: null,
            'status'   => $statusFilter ?: null,
            'priority' => $priorityFilter ?: null,
        ], fn ($v) => $v !== null && $v !== '');

        try {
            $response = $client->index($params);
        } catch (\Throwable $e) {
            \Filament\Notifications\Notification::make()
                ->title('Could not load tasks: ' . $e->getMessage())
                ->danger()
                ->send();

            $this->apiPaginator = new LengthAwarePaginator([], 0, $perPage, $page);

            return $this->apiPaginator;
        }

        // Handle both paginated ({ data: [], meta: {} }) and plain-array responses
        if (isset($response['data']) && is_array($response['data'])) {
            $items = collect($response['data'])
                ->map(fn (array $item) => $this->hydrateTask($item));

            $this->apiPaginator = new LengthAwarePaginator(
                $items,
                $response['meta']['total']        ?? count($items),
                $response['meta']['per_page']     ?? $perPage,
                $response['meta']['current_page'] ?? $page,
                ['path' => request()->url()]
            );
        } else {
            // Plain array — no pagination envelope
            $items = collect((array) $response)
                ->map(fn (array $item) => $this->hydrateTask($item));

            $this->apiPaginator = new LengthAwarePaginator(
                $items,
                count($items),
                $perPage,
                $page,
                ['path' => request()->url()]
            );
        }

        return $this->apiPaginator;
    }

    /**
     * Hydrate a plain data array into a Task model instance.
     * Setting $exists = true tells Filament this is a persisted record
     * so row actions (Edit, Delete) get the correct route key.
     */
    protected function hydrateTask(array $data): Task
    {
        $task = new Task();
        $task->forceFill($data);
        $task->exists = true;

        return $task;
    }
}