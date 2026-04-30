<?php

namespace Lightworx\FilamentTasksApi\Support\Paginators;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ApiPaginator extends LengthAwarePaginator
{
    public static function fromApi(array $response): self
    {
        $data = collect($response['data'] ?? []);

        $meta = $response['meta'] ?? [];

        return new self(
            $data,
            $meta['total'] ?? $data->count(),
            $meta['per_page'] ?? $data->count(),
            $meta['current_page'] ?? 1,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }
}