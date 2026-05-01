<?php

namespace Lightworx\FilamentTasksApi\Resources\TaskResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
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

    protected ?LengthAwarePaginator $apiPaginator = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return Task::query()->whereRaw('1 = 0');
    }

    public function getTableRecords(): \Illuminate\Contracts\Pagination\LengthAwarePaginator|Collection
    {
        if ($this->apiPaginator !== null) {
            return $this->apiPaginator;
        }

        /** @var TasksApiClient $client */
        $client = app(TasksApiClient::class);

        $perPage      = $this->getTableRecordsPerPage();
        $page         = max(1, $this->getPage());
        $tableFilters = $this->getTableFilters();

        try {
            $query = $client->tasks();

            // Apply filters from the Filament table UI
            if ($status = $tableFilters['status']['value'] ?? null) {
                $query->whereStatus($status);
            }

            if ($assignedEmail = $tableFilters['assigned_email']['value'] ?? null) {
                $query->whereAssignedTo($assignedEmail);
            }

            // Default to newest first
            $query->latest();

            $result = $query->paginate($perPage);

        } catch (\Throwable $e) {
            Notification::make()
                ->title('Could not load tasks: ' . $e->getMessage())
                ->danger()
                ->send();

            $this->apiPaginator = new LengthAwarePaginator([], 0, $perPage, $page);
            return $this->apiPaginator;
        }

        $items = collect($result['data'])
            ->map(fn ($dto) => Task::fromDto($dto));

        $meta = $result['meta'] ?? [];

        $this->apiPaginator = new LengthAwarePaginator(
            $items,
            $meta['total']        ?? count($items),
            $meta['per_page']     ?? $perPage,
            $meta['current_page'] ?? $page,
            ['path' => request()->url()]
        );

        return $this->apiPaginator;
    }
}