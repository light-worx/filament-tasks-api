<?php

namespace Lightworx\FilamentTasks\Resources\ProjectResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Lightworx\FilamentTasks\Models\Project;
use Lightworx\FilamentTasks\Resources\ProjectResource;
use Lightworx\TasksApiClient\Facades\TasksApi;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected ?LengthAwarePaginator $apiPaginator = null;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    protected function getTableQuery(): Builder
    {
        return Project::query()->whereRaw('1 = 0');
    }

    public function getTableRecords(): \Illuminate\Contracts\Pagination\LengthAwarePaginator|Collection
    {
        if ($this->apiPaginator !== null) {
            return $this->apiPaginator;
        }

        $perPage = $this->getTableRecordsPerPage();
        $page    = max(1, $this->getPage());

        try {
            $result = TasksApi::projects()->paginate($perPage);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Could not load projects: ' . $e->getMessage())
                ->danger()
                ->send();
            $this->apiPaginator = new LengthAwarePaginator([], 0, $perPage, $page);
            return $this->apiPaginator;
        }

        $items = collect($result['data'])->map(fn ($dto) => Project::fromDto($dto));
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
}