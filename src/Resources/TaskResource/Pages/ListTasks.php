<?php

namespace Lightworx\FilamentTasks\Resources\TaskResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Lightworx\FilamentTasks\Models\Task;
use Lightworx\FilamentTasks\Resources\TaskResource;
use Lightworx\FilamentTasks\Support\TaskCache;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected ?LengthAwarePaginator $apiPaginator = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $perPage      = $this->getTableRecordsPerPage();
                    $page         = max(1, $this->getPage());
                    $tableFilters = $this->getTableFilters();
                    $status       = $tableFilters['status']['value'] ?? null;

                    $this->apiPaginator = null; // clear cached paginator for this request

                    TaskCache::refreshTasks($page, $perPage, $status ?: null);

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

        $items = collect($result['data'])->map(fn ($dto) => Task::fromDto($dto));
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